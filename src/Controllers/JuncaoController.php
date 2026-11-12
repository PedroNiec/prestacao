<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Catalog\DocumentCatalog;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\ArquivoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProjetoRepository;
use App\Services\FileStorage;
use App\Services\JuncaoService;
use App\Services\PdfMergeException;

final class JuncaoController
{
    public function __construct(
        private ProjetoRepository $projetos,
        private ArquivoRepository $arquivos,
        private DocumentoRepository $documentos,
        private JuncaoService $juncao,
        private FileStorage $storage,
    ) {
    }

    public function gerenciar(Request $r, string $id, string $codigo): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        return Response::html(View::render('documentos/juncao', [
            'titulo' => 'Documento ' . $codigo,
            'projeto' => $this->projetos->find((int) $id),
            'doc' => DocumentCatalog::find($codigo),
            'arquivos' => $this->arquivos->list((int) $id, $codigo),
            'final' => $this->documentos->find((int) $id, $codigo),
        ]));
    }

    public function adicionar(Request $r, string $id, string $codigo): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        $res = $this->juncao->adicionar((int) $id, $codigo, self::normalizar($r->files['arquivos'] ?? []));
        if ($res['enviados'] > 0) {
            Session::flash($res['enviados'] . ' arquivo(s) adicionado(s). Gere o PDF novamente para incluí-los.');
        }
        foreach ($res['erros'] as $e) {
            Session::flash($e, 'erro');
        }
        if ($res['enviados'] === 0 && $res['erros'] === []) {
            Session::flash('Selecione ao menos um arquivo PDF.', 'erro');
        }
        return $this->voltar($id, $codigo);
    }

    public function remover(Request $r, string $id, string $codigo, string $aid): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        $this->juncao->remover((int) $id, $codigo, (int) $aid)
            ? Session::flash('Arquivo removido. Gere o PDF novamente.')
            : Session::flash('Arquivo não encontrado.', 'erro');
        return $this->voltar($id, $codigo);
    }

    public function reordenar(Request $r, string $id, string $codigo): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        $ordem = $r->post['ordem'] ?? [];
        try {
            $this->juncao->reordenar((int) $id, $codigo, is_array($ordem) ? $ordem : []);
            Session::flash('Ordem atualizada. Gere o PDF novamente.');
        } catch (\InvalidArgumentException $e) {
            Session::flash($e->getMessage(), 'erro');
        }
        return $this->voltar($id, $codigo);
    }

    public function mover(Request $r, string $id, string $codigo, string $aid): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        $this->juncao->mover((int) $id, $codigo, (int) $aid, ($r->post['direcao'] ?? '') === 'cima' ? -1 : 1);
        return $this->voltar($id, $codigo);
    }

    public function gerar(Request $r, string $id, string $codigo): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        try {
            $this->juncao->gerar((int) $id, $codigo);
            Session::flash('PDF gerado com sucesso.');
        } catch (PdfMergeException $e) {
            Session::flash($e->getMessage(), 'erro');
        }
        return $this->voltar($id, $codigo);
    }

    public function verArquivo(Request $r, string $id, string $codigo, string $aid): Response
    {
        if (($erro = $this->validar($id, $codigo)) !== null) {
            return $erro;
        }
        $a = $this->arquivos->find((int) $id, $codigo, (int) $aid);
        if ($a === null || !$this->storage->exists($a['caminho'])) {
            return $this->erro('Arquivo não encontrado.');
        }
        return Response::file($this->storage->absolute($a['caminho']), $a['nome_original'], 'application/pdf', true);
    }

    /** Converte a estrutura de $_FILES de campos múltiplos em uma lista de arquivos. */
    public static function normalizar(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return [$files];
        }
        $out = [];
        foreach (array_keys($files['name']) as $i) {
            $out[] = [
                'name' => $files['name'][$i] ?? '', 'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE, 'size' => $files['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    private function voltar(string $id, string $codigo): Response
    {
        return Response::redirect("/projetos/$id/documentos/$codigo");
    }

    private function validar(string $id, string $codigo): ?Response
    {
        if ($this->projetos->find((int) $id) === null) {
            return $this->erro('Projeto não encontrado.');
        }
        if (!JuncaoService::ehJuncao($codigo)) {
            return $this->erro('Documento não encontrado ou não é de junção de PDFs.');
        }
        return null;
    }

    private function erro(string $msg): Response
    {
        return Response::html(View::render('erro', ['titulo' => 'Não encontrado', 'mensagem' => $msg]), 404);
    }
}
