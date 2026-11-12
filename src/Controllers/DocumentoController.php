<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Catalog\DocumentCatalog;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\ProjetoRepository;
use App\Services\DocumentoService;
use App\Services\UploadException;

final class DocumentoController
{
    public function __construct(private ProjetoRepository $projetos, private DocumentoService $docs)
    {
    }

    public function enviar(Request $r, string $id, string $codigo): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        try {
            $this->docs->enviar((int) $id, $codigo, $r->files['arquivo'] ?? []);
            Session::flash('Documento ' . $codigo . (DocumentCatalog::ehConversao($codigo) ? ' convertido para PDF e salvo.' : ' enviado.'));
        } catch (UploadException $e) {
            Session::flash($e->getMessage(), 'erro');
        }
        return Response::redirect("/projetos/$id#doc-$codigo");
    }

    public function remover(Request $r, string $id, string $codigo): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        $this->docs->remover((int) $id, $codigo)
            ? Session::flash('Documento ' . $codigo . ' removido.')
            : Session::flash('Não há arquivo para remover.', 'erro');
        return Response::redirect("/projetos/$id#doc-$codigo");
    }

    public function baixar(Request $r, string $id, string $codigo): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        $arq = $this->docs->arquivo((int) $id, $codigo);
        if ($arq === null) {
            return $this->erro('Este documento ainda não possui arquivo.', 404);
        }
        $doc = DocumentCatalog::find($codigo);
        return Response::file($arq['path'], $doc['arquivo'], 'application/pdf', isset($r->query['ver']));
    }

    private function validar(string $id, string $codigo): ?Response
    {
        if ($this->projetos->find((int) $id) === null) {
            return $this->erro('Projeto não encontrado.', 404);
        }
        if (DocumentCatalog::find($codigo) === null) {
            return $this->erro('Documento não encontrado.', 404);
        }
        return null;
    }

    private function erro(string $msg, int $status): Response
    {
        return Response::html(View::render('erro', ['titulo' => 'Não encontrado', 'mensagem' => $msg]), $status);
    }
}
