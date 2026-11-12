<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Domain\Money;
use App\Domain\Validator;
use App\Repositories\ConciliacaoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProjetoRepository;
use App\Services\ConciliacaoService;

final class ConciliacaoController
{
    public function __construct(
        private ProjetoRepository $projetos,
        private ConciliacaoRepository $repo,
        private DocumentoRepository $documentos,
        private ConciliacaoService $service,
    ) {
    }

    public function index(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $dados = $this->service->dados((int) $id);
        $old = Session::old();
        if ($old) { // repopula o formulário de dados avulsos após erro de validação
            $dados['config'] = array_merge($dados['config'], $old);
        }
        return Response::html(View::render('conciliacao/index', $dados + [
            'titulo' => 'Conciliação bancária',
            'final' => $this->documentos->find((int) $id, ConciliacaoService::CODIGO),
        ]));
    }

    public function salvarReceitas(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $valores = $r->post['valores'] ?? [];
        $erros = $this->service->salvarReceitas((int) $id, is_array($valores) ? $valores : []);
        if ($erros) {
            foreach ($erros as $m) {
                Session::flash($m, 'erro');
            }
        } else {
            Session::flash('Receitas salvas.');
        }
        return Response::redirect("/projetos/$id/conciliacao#receitas");
    }

    public function restaurar(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $this->service->restaurar((int) $id, (string) ($r->post['chave'] ?? ''))
            ? Session::flash('Valor restaurado para o calculado a partir do balancete.')
            : Session::flash('Linha não encontrada.', 'erro');
        return Response::redirect("/projetos/$id/conciliacao#receitas");
    }

    public function salvarConfig(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $v = Validator::conciliacaoConfig($r->post);
        if ($v['erros']) {
            foreach ($v['erros'] as $m) {
                Session::flash($m, 'erro');
            }
            Session::old($r->post);
            return Response::redirect("/projetos/$id/conciliacao#dados");
        }
        $this->service->salvarConfig((int) $id, $v['dados']);
        Session::flash('Dados da conciliação salvos.');
        return Response::redirect("/projetos/$id/conciliacao#dados");
    }

    public function notaNova(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        return $this->form('Nova nota fiscal', $id, Session::old() ?: [], "/projetos/$id/conciliacao/notas", false);
    }

    public function notaCriar(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $v = Validator::conciliacaoNota($r->post);
        if ($v['erros']) {
            return $this->voltar("/projetos/$id/conciliacao/notas/novo", $v['erros'], $r->post);
        }
        $this->service->criarNota((int) $id, $v['dados']);
        Session::flash('Nota fiscal adicionada.');
        return Response::redirect("/projetos/$id/conciliacao#notas");
    }

    public function notaEditar(Request $r, string $id, string $nid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $n = $this->repo->nota((int) $id, (int) $nid);
        if ($n === null) {
            return $this->erro('Nota fiscal não encontrada.');
        }
        $dados = Session::old();
        if (!$dados) {
            $dados = ['nota_fiscal' => $n['nota_fiscal'], 'bruto' => Money::input((int) $n['bruto_centavos']), 'ir' => Money::input((int) $n['ir_centavos']),
                'iss' => Money::input((int) $n['iss_centavos']), 'inss' => Money::input((int) $n['inss_centavos']), 'outros' => Money::input((int) $n['outros_centavos'])];
        }
        return $this->form('Editar nota fiscal', $id, $dados, "/projetos/$id/conciliacao/notas/$nid", true);
    }

    public function notaAtualizar(Request $r, string $id, string $nid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        if ($this->repo->nota((int) $id, (int) $nid) === null) {
            return $this->erro('Nota fiscal não encontrada.');
        }
        $v = Validator::conciliacaoNota($r->post);
        if ($v['erros']) {
            return $this->voltar("/projetos/$id/conciliacao/notas/$nid/editar", $v['erros'], $r->post);
        }
        $this->service->atualizarNota((int) $id, (int) $nid, $v['dados']);
        Session::flash('Nota fiscal atualizada.');
        return Response::redirect("/projetos/$id/conciliacao#notas");
    }

    public function notaExcluir(Request $r, string $id, string $nid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        if ($this->repo->nota((int) $id, (int) $nid) === null) {
            return $this->erro('Nota fiscal não encontrada.');
        }
        $this->service->excluirNota((int) $id, (int) $nid);
        Session::flash('Nota fiscal excluída.');
        return Response::redirect("/projetos/$id/conciliacao#notas");
    }

    public function notaMover(Request $r, string $id, string $nid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $this->service->moverNota((int) $id, (int) $nid, ($r->post['direcao'] ?? '') === 'cima' ? -1 : 1);
        return Response::redirect("/projetos/$id/conciliacao#notas");
    }

    public function gerar(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        try {
            $this->service->gerar((int) $id);
            Session::flash('PDF da conciliação gerado.');
        } catch (\RuntimeException $ex) {
            error_log((string) $ex);
            Session::flash($ex->getMessage(), 'erro');
        }
        return Response::redirect("/projetos/$id/conciliacao");
    }

    private function form(string $titulo, string $id, array $dados, string $action, bool $edicao): Response
    {
        return Response::html(View::render('conciliacao/nota_form', [
            'titulo' => $titulo, 'projeto' => $this->projetos->find((int) $id), 'n' => $dados, 'action' => $action, 'edicao' => $edicao,
        ]));
    }

    private function voltar(string $to, array $erros, array $post): Response
    {
        foreach ($erros as $m) {
            Session::flash($m, 'erro');
        }
        Session::old($post);
        return Response::redirect($to);
    }

    private function validar(string $id): ?Response
    {
        return $this->projetos->find((int) $id) === null ? $this->erro('Projeto não encontrado.') : null;
    }

    private function erro(string $msg): Response
    {
        return Response::html(View::render('erro', ['titulo' => 'Não encontrado', 'mensagem' => $msg]), 404);
    }
}
