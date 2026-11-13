<?php
declare(strict_types=1);

use App\Controllers\BalanceteController;
use App\Controllers\ConciliacaoController;
use App\Controllers\DocumentoController;
use App\Controllers\BensController;
use App\Controllers\ZipController;
use App\Controllers\FisicoFinanceiroController;
use App\Controllers\JuncaoController;
use App\Controllers\MunicipioController;
use App\Controllers\ProjetoController;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;
use App\Repositories\ArquivoRepository;
use App\Repositories\ConciliacaoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\BensRepository;
use App\Repositories\FisicoFinanceiroRepository;
use App\Repositories\LancamentoRepository;
use App\Repositories\MunicipioRepository;
use App\Repositories\OverrideRepository;
use App\Repositories\ProjetoRepository;
use App\Services\BalanceteService;
use App\Services\ConciliacaoService;
use App\Services\ConversionService;
use App\Services\DocumentoService;
use App\Services\FileStorage;
use App\Services\BensService;
use App\Services\ZipService;
use App\Services\FisicoFinanceiroService;
use App\Services\JuncaoService;
use App\Services\PdfMergeService;
use App\Services\ReportPdfService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

/** Prepara banco e pastas de armazenamento. */
/** @return string[] migrações aplicadas */
function prepare_storage(): array
{
    $root = dirname(__DIR__) . '/storage';
    foreach (['', '/uploads', '/generated'] as $sub) {
        if (!is_dir($root . $sub) && !mkdir($root . $sub, 0775, true) && !is_dir($root . $sub)) {
            throw new RuntimeException("Não foi possível criar $root$sub");
        }
    }
    return (new Migrator(Database::connection(), dirname(__DIR__) . '/database/migrations'))->run();
}

/** Monta as rotas da aplicação. */
function build_router(): Router
{
    $router = new Router();
    $pdo = Database::connection();
    $storage = FileStorage::default();
    $docService = new DocumentoService(new DocumentoRepository($pdo), $storage, new ConversionService(new PdfMergeService()));
    $doc = new DocumentoController(new ProjetoRepository($pdo), $docService);
    $juncao = new JuncaoController(
        new ProjetoRepository($pdo), new ArquivoRepository($pdo), new DocumentoRepository($pdo),
        new JuncaoService(new ArquivoRepository($pdo), new DocumentoRepository($pdo), $storage, new PdfMergeService()),
        $storage,
    );
    $lancRepo = new LancamentoRepository($pdo);
    $balancete = new BalanceteController(
        new ProjetoRepository($pdo), $lancRepo, new DocumentoRepository($pdo),
        new BalanceteService($lancRepo, new DocumentoRepository($pdo), new ProjetoRepository($pdo), $storage, new ReportPdfService()),
    );
    $fisRepo = new FisicoFinanceiroRepository($pdo);
    $fisico = new FisicoFinanceiroController(
        new ProjetoRepository($pdo), $fisRepo, new DocumentoRepository($pdo),
        new FisicoFinanceiroService($fisRepo, new DocumentoRepository($pdo), new ProjetoRepository($pdo), $storage, new ReportPdfService()),
    );
    $concRepo = new ConciliacaoRepository($pdo);
    $conciliacao = new ConciliacaoController(
        new ProjetoRepository($pdo), $concRepo, new DocumentoRepository($pdo),
        new ConciliacaoService($concRepo, new OverrideRepository($pdo), $lancRepo, new DocumentoRepository($pdo), new ProjetoRepository($pdo), $storage, new ReportPdfService()),
    );
    $bensRepo = new BensRepository($pdo);
    $bens = new BensController(
        new ProjetoRepository($pdo), $bensRepo, new DocumentoRepository($pdo),
        new BensService($bensRepo, new DocumentoRepository($pdo), new ProjetoRepository($pdo), $storage, new ReportPdfService()),
    );
    $zip = new ZipController(new ProjetoRepository($pdo), new ZipService(new DocumentoRepository($pdo), new ProjetoRepository($pdo), $storage));
    $mun = new MunicipioController(new MunicipioRepository($pdo));
    $proj = new ProjetoController(new ProjetoRepository($pdo), new MunicipioRepository($pdo), new DocumentoRepository($pdo), $docService, new ArquivoRepository($pdo), new LancamentoRepository($pdo), new FisicoFinanceiroRepository($pdo), new ConciliacaoRepository($pdo), $bensRepo);

    $router->get('/', fn (Request $r) => Response::redirect('/projetos'));
    $router->get('/guia-visual', fn (Request $r) => Response::html(View::render('guia_visual', ['titulo' => 'Guia visual'])));

    $router->get('/municipios', $mun->index(...));
    $router->get('/municipios/novo', $mun->novo(...));
    $router->post('/municipios', $mun->criar(...));
    $router->get('/municipios/{id}/editar', $mun->editar(...));
    $router->post('/municipios/{id}', $mun->atualizar(...));
    $router->post('/municipios/{id}/excluir', $mun->excluir(...));

    $router->get('/projetos', $proj->index(...));
    $router->get('/projetos/novo', $proj->novo(...));
    $router->post('/projetos', $proj->criar(...));
    $router->get('/projetos/{id}', $proj->mostrar(...));
    $router->get('/projetos/{id}/editar', $proj->editar(...));
    $router->post('/projetos/{id}', $proj->atualizar(...));
    $router->post('/projetos/{id}/excluir', $proj->excluir(...));
    $router->get('/projetos/{id}/zip', $zip->baixar(...));

    $router->post('/projetos/{id}/documentos/{codigo}/enviar', $doc->enviar(...));
    $router->post('/projetos/{id}/documentos/{codigo}/remover', $doc->remover(...));
    $router->get('/projetos/{id}/documentos/{codigo}/baixar', $doc->baixar(...));

    $base = '/projetos/{id}/documentos/{codigo}';
    $router->get($base, $juncao->gerenciar(...));
    $router->post("$base/arquivos", $juncao->adicionar(...));
    $router->post("$base/arquivos/ordem", $juncao->reordenar(...));
    $router->post("$base/arquivos/{aid}/remover", $juncao->remover(...));
    $router->post("$base/arquivos/{aid}/mover", $juncao->mover(...));
    $router->get("$base/arquivos/{aid}/ver", $juncao->verArquivo(...));
    $router->post("$base/gerar", $juncao->gerar(...));

    $b = '/projetos/{id}/balancete';
    $router->get($b, $balancete->index(...));
    $router->post("$b/gerar", $balancete->gerar(...));
    $router->get("$b/lancamentos/novo", $balancete->novo(...));
    $router->post("$b/lancamentos", $balancete->criar(...));
    $router->get("$b/lancamentos/{lid}/editar", $balancete->editar(...));
    $router->post("$b/lancamentos/{lid}", $balancete->atualizar(...));
    $router->post("$b/lancamentos/{lid}/excluir", $balancete->excluir(...));
    $router->post("$b/lancamentos/{lid}/mover", $balancete->mover(...));

    $f = '/projetos/{id}/fisico-financeiro';
    $router->get($f, $fisico->index(...));
    $router->post("$f/gerar", $fisico->gerar(...));
    $router->get("$f/linhas/novo", $fisico->novo(...));
    $router->post("$f/linhas", $fisico->criar(...));
    $router->get("$f/linhas/{lid}/editar", $fisico->editar(...));
    $router->post("$f/linhas/{lid}", $fisico->atualizar(...));
    $router->post("$f/linhas/{lid}/excluir", $fisico->excluir(...));
    $router->post("$f/linhas/{lid}/mover", $fisico->mover(...));

    $rb = '/projetos/{id}/relacao-de-bens';
    $router->get($rb, $bens->index(...));
    $router->post("$rb/gerar", $bens->gerar(...));
    $router->post("$rb/titulo", $bens->titulo(...));
    $router->get("$rb/bens/novo", $bens->novo(...));
    $router->post("$rb/bens", $bens->criar(...));
    $router->get("$rb/bens/{bid}/editar", $bens->editar(...));
    $router->post("$rb/bens/{bid}", $bens->atualizar(...));
    $router->post("$rb/bens/{bid}/excluir", $bens->excluir(...));
    $router->post("$rb/bens/{bid}/mover", $bens->mover(...));

    $c = '/projetos/{id}/conciliacao';
    $router->get($c, $conciliacao->index(...));
    $router->post("$c/gerar", $conciliacao->gerar(...));
    $router->post("$c/receitas", $conciliacao->salvarReceitas(...));
    $router->post("$c/receitas/restaurar", $conciliacao->restaurar(...));
    $router->post("$c/config", $conciliacao->salvarConfig(...));
    $router->get("$c/notas/novo", $conciliacao->notaNova(...));
    $router->post("$c/notas", $conciliacao->notaCriar(...));
    $router->get("$c/notas/{nid}/editar", $conciliacao->notaEditar(...));
    $router->post("$c/notas/{nid}", $conciliacao->notaAtualizar(...));
    $router->post("$c/notas/{nid}/excluir", $conciliacao->notaExcluir(...));
    $router->post("$c/notas/{nid}/mover", $conciliacao->notaMover(...));
    return $router;
}

/** Trata uma requisição HTTP completa (segurança + roteamento). */
function handle(Request $request, string $host): Response
{
    // Proteção contra DNS rebinding: só aceita acesso por localhost.
    $hostName = strtolower(preg_replace('/:\d+$/', '', $host));
    if (!in_array($hostName, ['localhost', '127.0.0.1', '[::1]'], true)) {
        return Response::html(View::render('erro', ['titulo' => 'Acesso negado', 'mensagem' => 'O sistema só pode ser acessado localmente.']), 403);
    }

    if ($request->method === 'POST' && !Csrf::valid($request->post['_csrf'] ?? null)) {
        return Response::html(View::render('erro', ['titulo' => 'Sessão expirada', 'mensagem' => 'Token de segurança inválido. Volte e tente novamente.']), 419);
    }

    try {
        return build_router()->dispatch($request);
    } catch (Throwable $e) {
        error_log((string) $e);
        return Response::html(View::render('erro', ['titulo' => 'Erro interno', 'mensagem' => 'Ocorreu um erro inesperado. Detalhes foram registrados no log.']), 500);
    }
}
