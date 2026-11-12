<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Catalog\DocumentCatalog;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Domain\Validator;
use App\Repositories\ArquivoRepository;
use App\Repositories\ConciliacaoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\FisicoFinanceiroRepository;
use App\Repositories\LancamentoRepository;
use App\Repositories\MunicipioRepository;
use App\Repositories\ProjetoRepository;
use App\Services\DocumentoService;

final class ProjetoController
{
    public function __construct(
        private ProjetoRepository $projetos,
        private MunicipioRepository $municipios,
        private DocumentoRepository $documentos,
        private DocumentoService $docService,
        private ArquivoRepository $arquivos,
        private LancamentoRepository $lancamentos,
        private FisicoFinanceiroRepository $fisico,
        private ConciliacaoRepository $conciliacao,
    ) {
    }

    public function index(Request $r): Response
    {
        return Response::html(View::render('projetos/index', [
            'titulo' => 'Projetos', 'projetos' => $this->projetos->all(),
            'temMunicipios' => $this->municipios->all() !== [],
        ]));
    }

    public function novo(Request $r): Response
    {
        $municipios = $this->municipios->all();
        if ($municipios === []) {
            Session::flash('Cadastre um município antes de criar um projeto.', 'erro');
            return Response::redirect('/municipios/novo');
        }
        $old = Session::old();
        if (!$old && isset($r->query['municipio_id'])) {
            $old = ['municipio_id' => (int) $r->query['municipio_id']];
        }
        return Response::html(View::render('projetos/form', [
            'titulo' => 'Novo projeto', 'projeto' => $old, 'municipios' => $municipios,
            'action' => '/projetos', 'id' => null,
        ]));
    }

    public function criar(Request $r): Response
    {
        $m = $this->municipios->find((int) ($r->post['municipio_id'] ?? 0));
        $v = Validator::projeto($r->post, $m);
        if ($v['erros']) {
            return $this->voltar('/projetos/novo', $v['erros'], $r->post);
        }
        $id = $this->projetos->create($v['dados']);
        Session::flash('Projeto criado.');
        return Response::redirect("/projetos/$id");
    }

    public function mostrar(Request $r, string $id): Response
    {
        $p = $this->projetos->find((int) $id);
        if ($p === null) {
            return $this->naoEncontrado();
        }
        return Response::html(View::render('projetos/show', [
            'titulo' => 'Projeto #' . $p['id'], 'projeto' => $p, 'documentos' => DocumentCatalog::all(),
            'enviados' => $this->documentos->byProjeto((int) $id),
            'contagens' => $this->arquivos->countsByProjeto((int) $id),
            'relatorios' => [
                '01' => ['url' => "/projetos/$id/balancete", 'rotulo' => 'Abrir balancete', 'n' => $this->lancamentos->count((int) $id), 'unidade' => 'lançamento(s)'],
                '22' => ['url' => "/projetos/$id/fisico-financeiro", 'rotulo' => 'Abrir físico-financeiro', 'n' => $this->fisico->count((int) $id), 'unidade' => 'linha(s)'],
                '15' => ['url' => "/projetos/$id/conciliacao", 'rotulo' => 'Abrir conciliação', 'n' => $this->conciliacao->countNotas((int) $id), 'unidade' => 'nota(s) fiscal(is)'],
            ],
        ]));
    }

    public function editar(Request $r, string $id): Response
    {
        $p = $this->projetos->find((int) $id);
        if ($p === null) {
            return $this->naoEncontrado();
        }
        return Response::html(View::render('projetos/form', [
            'titulo' => 'Editar projeto', 'projeto' => Session::old() ?: $p,
            'municipios' => $this->municipios->all(), 'action' => "/projetos/$id", 'id' => (int) $id,
        ]));
    }

    public function atualizar(Request $r, string $id): Response
    {
        if ($this->projetos->find((int) $id) === null) {
            return $this->naoEncontrado();
        }
        $m = $this->municipios->find((int) ($r->post['municipio_id'] ?? 0));
        $v = Validator::projeto($r->post, $m);
        if ($v['erros']) {
            return $this->voltar("/projetos/$id/editar", $v['erros'], $r->post);
        }
        $this->projetos->update((int) $id, $v['dados']);
        Session::flash('Projeto atualizado.');
        return Response::redirect("/projetos/$id");
    }

    public function excluir(Request $r, string $id): Response
    {
        if ($this->projetos->find((int) $id) === null) {
            return $this->naoEncontrado();
        }
        $this->projetos->delete((int) $id);
        $this->docService->removerArquivosDoProjeto((int) $id);
        Session::flash('Projeto excluído.');
        return Response::redirect('/projetos');
    }

    private function voltar(string $to, array $erros, array $post): Response
    {
        foreach ($erros as $e) {
            Session::flash($e, 'erro');
        }
        Session::old($post);
        return Response::redirect($to);
    }

    private function naoEncontrado(): Response
    {
        return Response::html(View::render('erro', ['titulo' => 'Não encontrado', 'mensagem' => 'Projeto não encontrado.']), 404);
    }
}
