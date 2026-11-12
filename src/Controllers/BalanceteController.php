<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Domain\BalanceteCalculator;
use App\Domain\Money;
use App\Domain\Validator;
use App\Repositories\DocumentoRepository;
use App\Repositories\LancamentoRepository;
use App\Repositories\ProjetoRepository;
use App\Services\BalanceteService;

final class BalanceteController
{
    public function __construct(
        private ProjetoRepository $projetos,
        private LancamentoRepository $lancamentos,
        private DocumentoRepository $documentos,
        private BalanceteService $service,
    ) {
    }

    public function index(Request $r, string $id): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        $dados = $this->service->dados((int) $id);
        return Response::html(View::render('balancete/index', $dados + [
            'titulo' => 'Balancete',
            'final' => $this->documentos->find((int) $id, BalanceteService::CODIGO),
        ]));
    }

    public function novo(Request $r, string $id): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        $old = Session::old();
        $base = $old ?: array_intersect_key($r->query, array_flip(['tipo', 'origem', 'fonte', 'data', 'boletim_parcela'])) + ['tipo' => 'despesa', 'origem' => 'convenio'];
        return $this->form('Novo lançamento', $id, $base, "/projetos/$id/balancete/lancamentos", false);
    }

    public function criar(Request $r, string $id): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        $v = Validator::lancamento($r->post);
        if ($v['erros']) {
            return $this->voltar("/projetos/$id/balancete/lancamentos/novo", $v['erros'], $r->post);
        }
        $this->service->criar((int) $id, $v['dados']);
        Session::flash('Lançamento adicionado.');
        if (isset($r->post['outro'])) {
            $d = $v['dados'];
            $q = http_build_query(['tipo' => $d['tipo'], 'origem' => $d['origem'], 'fonte' => $d['fonte'], 'data' => $d['data']]);
            return Response::redirect("/projetos/$id/balancete/lancamentos/novo?$q");
        }
        return Response::redirect("/projetos/$id/balancete");
    }

    public function editar(Request $r, string $id, string $lid): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        $l = $this->lancamentos->find((int) $id, (int) $lid);
        if ($l === null) {
            return $this->erro('Lançamento não encontrado.');
        }
        $old = Session::old();
        $dados = $old ?: $l + ['valor' => Money::input((int) $l['valor_centavos'])];
        return $this->form('Editar lançamento', $id, $dados, "/projetos/$id/balancete/lancamentos/$lid", true);
    }

    public function atualizar(Request $r, string $id, string $lid): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        if ($this->lancamentos->find((int) $id, (int) $lid) === null) {
            return $this->erro('Lançamento não encontrado.');
        }
        $v = Validator::lancamento($r->post);
        if ($v['erros']) {
            return $this->voltar("/projetos/$id/balancete/lancamentos/$lid/editar", $v['erros'], $r->post);
        }
        $this->service->atualizar((int) $id, (int) $lid, $v['dados']);
        Session::flash('Lançamento atualizado.');
        return Response::redirect("/projetos/$id/balancete");
    }

    public function excluir(Request $r, string $id, string $lid): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        if ($this->lancamentos->find((int) $id, (int) $lid) === null) {
            return $this->erro('Lançamento não encontrado.');
        }
        $this->service->excluir((int) $id, (int) $lid);
        Session::flash('Lançamento excluído.');
        return Response::redirect("/projetos/$id/balancete");
    }

    public function mover(Request $r, string $id, string $lid): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        $this->service->mover((int) $id, (int) $lid, ($r->post['direcao'] ?? '') === 'cima' ? -1 : 1);
        return Response::redirect("/projetos/$id/balancete#l$lid");
    }

    public function gerar(Request $r, string $id): Response
    {
        if (($erro = $this->validar($id)) !== null) {
            return $erro;
        }
        try {
            $this->service->gerar((int) $id);
            Session::flash('PDF do balancete gerado.');
        } catch (\RuntimeException $e) {
            error_log((string) $e);
            Session::flash($e->getMessage(), 'erro');
        }
        return Response::redirect("/projetos/$id/balancete");
    }

    private function form(string $titulo, string $id, array $dados, string $action, bool $edicao): Response
    {
        return Response::html(View::render('balancete/form', [
            'titulo' => $titulo, 'projeto' => $this->projetos->find((int) $id), 'l' => $dados,
            'action' => $action, 'edicao' => $edicao, 'origens' => BalanceteCalculator::ORIGENS,
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
