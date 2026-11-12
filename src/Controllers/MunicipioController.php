<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Domain\Validator;
use App\Repositories\MunicipioRepository;

final class MunicipioController
{
    public function __construct(private MunicipioRepository $repo)
    {
    }

    public function index(Request $r): Response
    {
        return Response::html(View::render('municipios/index', [
            'titulo' => 'Municípios', 'municipios' => $this->repo->all(),
        ]));
    }

    public function novo(Request $r): Response
    {
        return Response::html(View::render('municipios/form', [
            'titulo' => 'Novo município', 'municipio' => Session::old() ?: [],
            'action' => '/municipios', 'id' => null,
        ]));
    }

    public function criar(Request $r): Response
    {
        $v = Validator::municipio($r->post);
        if ($v['erros']) {
            return $this->voltar('/municipios/novo', $v['erros'], $r->post);
        }
        $this->repo->create($v['dados']);
        Session::flash('Município cadastrado.');
        return Response::redirect('/municipios');
    }

    public function editar(Request $r, string $id): Response
    {
        $m = $this->repo->find((int) $id);
        if ($m === null) {
            return $this->naoEncontrado();
        }
        return Response::html(View::render('municipios/form', [
            'titulo' => 'Editar município', 'municipio' => Session::old() ?: $m,
            'action' => "/municipios/$id", 'id' => (int) $id,
        ]));
    }

    public function atualizar(Request $r, string $id): Response
    {
        if ($this->repo->find((int) $id) === null) {
            return $this->naoEncontrado();
        }
        $v = Validator::municipio($r->post);
        if ($v['erros']) {
            return $this->voltar("/municipios/$id/editar", $v['erros'], $r->post);
        }
        $this->repo->update((int) $id, $v['dados']);
        Session::flash('Município atualizado. Projetos existentes mantêm suas assinaturas.');
        return Response::redirect('/municipios');
    }

    public function excluir(Request $r, string $id): Response
    {
        if ($this->repo->find((int) $id) === null) {
            return $this->naoEncontrado();
        }
        $n = $this->repo->countProjetos((int) $id);
        if ($n > 0) {
            Session::flash("Não é possível excluir: o município possui $n projeto(s). Exclua os projetos antes.", 'erro');
            return Response::redirect('/municipios');
        }
        $this->repo->delete((int) $id);
        Session::flash('Município excluído.');
        return Response::redirect('/municipios');
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
        return Response::html(View::render('erro', ['titulo' => 'Não encontrado', 'mensagem' => 'Município não encontrado.']), 404);
    }
}
