<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Domain\Money;
use App\Domain\Validator;
use App\Repositories\BensRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProjetoRepository;
use App\Services\BensService;

/** Relação de bens (doc. 09) — specs/relacao-de-bens.md. */
final class BensController
{
    private const TITULO_MAX = 200;

    public function __construct(
        private ProjetoRepository $projetos,
        private BensRepository $bens,
        private DocumentoRepository $documentos,
        private BensService $service,
    ) {
    }

    private function base(string $id): string
    {
        return "/projetos/$id/relacao-de-bens";
    }

    public function index(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        return Response::html(View::render('bens/index', $this->service->dados((int) $id) + [
            'titulo' => 'Relação de bens',
            'final' => $this->documentos->find((int) $id, BensService::CODIGO),
        ]));
    }

    public function novo(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        // DEC-B1: o primeiro bem vem com a descrição = objeto do projeto (como na planilha)
        $sugestao = $this->bens->count((int) $id) === 0 ? ['descricao' => (string) $this->projetos->find((int) $id)['objeto']] : [];
        return $this->form('Novo bem', $id, Session::old() ?: $sugestao, $this->base($id) . '/bens', false);
    }

    public function criar(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $v = Validator::bem($r->post);
        if ($v['erros']) {
            return $this->voltar($this->base($id) . '/bens/novo', $v['erros'], $r->post);
        }
        $this->service->criar((int) $id, $v['dados']);
        Session::flash('Bem adicionado.');
        return Response::redirect($this->base($id));
    }

    public function editar(Request $r, string $id, string $bid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $b = $this->bens->find((int) $id, (int) $bid);
        if ($b === null) {
            return $this->erro('Bem não encontrado.');
        }
        $dados = Session::old() ?: ['descricao' => $b['descricao'], 'valor' => Money::input((int) $b['valor_centavos'])];
        return $this->form('Editar bem', $id, $dados, $this->base($id) . "/bens/$bid", true);
    }

    public function atualizar(Request $r, string $id, string $bid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        if ($this->bens->find((int) $id, (int) $bid) === null) {
            return $this->erro('Bem não encontrado.');
        }
        $v = Validator::bem($r->post);
        if ($v['erros']) {
            return $this->voltar($this->base($id) . "/bens/$bid/editar", $v['erros'], $r->post);
        }
        $this->service->atualizar((int) $id, (int) $bid, $v['dados']);
        Session::flash('Bem atualizado.');
        return Response::redirect($this->base($id));
    }

    public function excluir(Request $r, string $id, string $bid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        if ($this->bens->find((int) $id, (int) $bid) === null) {
            return $this->erro('Bem não encontrado.');
        }
        $this->service->excluir((int) $id, (int) $bid);
        Session::flash('Bem excluído.');
        return Response::redirect($this->base($id));
    }

    public function mover(Request $r, string $id, string $bid): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $this->service->mover((int) $id, (int) $bid, ($r->post['direcao'] ?? '') === 'cima' ? -1 : 1);
        return Response::redirect($this->base($id) . "#b$bid");
    }

    public function titulo(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        $titulo = trim((string) preg_replace('/\s+/u', ' ', (string) ($r->post['titulo_documento'] ?? '')));
        if (mb_strlen($titulo) > self::TITULO_MAX) {
            Session::flash('O título pode ter no máximo ' . self::TITULO_MAX . ' caracteres.', 'erro');
            return Response::redirect($this->base($id) . '#titulo');
        }
        $this->service->salvarTitulo((int) $id, $titulo);
        Session::flash($titulo === '' ? 'Título restaurado para o padrão.' : 'Título salvo.');
        return Response::redirect($this->base($id) . '#titulo');
    }

    public function gerar(Request $r, string $id): Response
    {
        if (($e = $this->validar($id)) !== null) {
            return $e;
        }
        try {
            $this->service->gerar((int) $id);
            Session::flash('PDF da relação de bens gerado.');
        } catch (\RuntimeException $ex) {
            error_log((string) $ex);
            Session::flash($ex->getMessage(), 'erro');
        }
        return Response::redirect($this->base($id));
    }

    private function form(string $titulo, string $id, array $dados, string $action, bool $edicao): Response
    {
        return Response::html(View::render('bens/form', [
            'titulo' => $titulo, 'projeto' => $this->projetos->find((int) $id), 'b' => $dados, 'action' => $action, 'edicao' => $edicao,
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
