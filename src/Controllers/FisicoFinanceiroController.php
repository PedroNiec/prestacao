<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Domain\Money;
use App\Domain\Validator;
use App\Repositories\DocumentoRepository;
use App\Repositories\FisicoFinanceiroRepository;
use App\Repositories\ProjetoRepository;
use App\Services\FisicoFinanceiroService;

final class FisicoFinanceiroController
{
    public function __construct(
        private ProjetoRepository $projetos,
        private FisicoFinanceiroRepository $linhas,
        private DocumentoRepository $documentos,
        private FisicoFinanceiroService $service,
    ) {
    }

    public function index(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        return Response::html(View::render('fisico/index', $this->service->dados((int) $id) + [
            'titulo' => 'Físico-financeiro',
            'final' => $this->documentos->find((int) $id, FisicoFinanceiroService::CODIGO),
        ]));
    }

    public function novo(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $projeto = $this->projetos->find((int) $id);
        $n = $this->linhas->count((int) $id) + 1;
        // Sugestão: descrição = objeto do projeto (como na planilha); meta/etapa sequenciais.
        $base = Session::old() ?: ['meta' => (string) $n, 'etapa' => '1', 'descricao' => $projeto['objeto'], 'unidade' => 'Unid'];
        return $this->form('Nova meta/etapa', $id, $base, "/projetos/$id/fisico-financeiro/linhas", false);
    }

    public function criar(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $v = Validator::fisicoFinanceiro($r->post);
        if ($v['erros']) {
            return $this->voltar("/projetos/$id/fisico-financeiro/linhas/novo", $v['erros'], $r->post);
        }
        $this->service->criar((int) $id, $v['dados']);
        Session::flash('Meta/etapa adicionada.');
        return Response::redirect("/projetos/$id/fisico-financeiro");
    }

    public function editar(Request $r, string $id, string $lid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $l = $this->linhas->find((int) $id, (int) $lid);
        if ($l === null) {
            return $this->erro('Linha não encontrada.');
        }
        $dados = Session::old();
        if (!$dados) {
            $dados = $l;
            foreach (array_keys(Validator::FISICO_QUANTIDADES + Validator::FISICO_VALORES) as $c) {
                $dados[$c] = Money::input((int) $l[$c]);
            }
        }
        return $this->form('Editar meta/etapa', $id, $dados, "/projetos/$id/fisico-financeiro/linhas/$lid", true);
    }

    public function atualizar(Request $r, string $id, string $lid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        if ($this->linhas->find((int) $id, (int) $lid) === null) {
            return $this->erro('Linha não encontrada.');
        }
        $v = Validator::fisicoFinanceiro($r->post);
        if ($v['erros']) {
            return $this->voltar("/projetos/$id/fisico-financeiro/linhas/$lid/editar", $v['erros'], $r->post);
        }
        $this->service->atualizar((int) $id, (int) $lid, $v['dados']);
        Session::flash('Meta/etapa atualizada.');
        return Response::redirect("/projetos/$id/fisico-financeiro");
    }

    public function excluir(Request $r, string $id, string $lid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        if ($this->linhas->find((int) $id, (int) $lid) === null) {
            return $this->erro('Linha não encontrada.');
        }
        $this->service->excluir((int) $id, (int) $lid);
        Session::flash('Meta/etapa excluída.');
        return Response::redirect("/projetos/$id/fisico-financeiro");
    }

    public function mover(Request $r, string $id, string $lid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $this->service->mover((int) $id, (int) $lid, ($r->post['direcao'] ?? '') === 'cima' ? -1 : 1);
        return Response::redirect("/projetos/$id/fisico-financeiro#l$lid");
    }

    public function gerar(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        try {
            $this->service->gerar((int) $id);
            Session::flash('PDF do físico-financeiro gerado.');
        } catch (\RuntimeException $ex) {
            error_log((string) $ex);
            Session::flash($ex->getMessage(), 'erro');
        }
        return Response::redirect("/projetos/$id/fisico-financeiro");
    }

    private function form(string $titulo, string $id, array $dados, string $action, bool $edicao): Response
    {
        return Response::html(View::render('fisico/form', [
            'titulo' => $titulo, 'projeto' => $this->projetos->find((int) $id), 'l' => $dados, 'action' => $action, 'edicao' => $edicao,
        ]));
    }

    private function voltar(string $to, array $erros, array $post): Response
    {
        foreach ($erros as $e) {
            Session::flash($e, 'erro');
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
