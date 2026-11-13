<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\ProjetoRepository;
use App\Services\ZipService;

/** Download do ZIP da prestação de contas (specs/zip-prestacao.md). */
final class ZipController
{
    public function __construct(private ProjetoRepository $projetos, private ZipService $zip)
    {
    }

    public function baixar(Request $r, string $id): Response
    {
        if ($this->projetos->find((int) $id) === null) {   // EX-Z04
            return Response::html(View::render('erro', ['titulo' => 'Não encontrado', 'mensagem' => 'Projeto não encontrado.']), 404);
        }
        try {
            $z = $this->zip->montar((int) $id);
        } catch (\RuntimeException $e) {                    // EX-Z03
            error_log((string) $e);
            Session::flash('Não foi possível gerar o ZIP: ' . $e->getMessage(), 'erro');
            return Response::redirect("/projetos/$id");
        }
        foreach ($z['ausentes'] as $codigo) {               // EX-Z02 (aparece na próxima página)
            Session::flash("O documento $codigo não foi incluído no ZIP: arquivo não encontrado.", 'erro');
        }
        if ($z['caminho'] === null) {                       // EX-Z01 (= EX-05)
            Session::flash('Não há documentos para baixar.', 'erro');
            return Response::redirect("/projetos/$id");
        }
        return Response::tempFile($z['caminho'], $z['nome'], 'application/zip');
    }
}
