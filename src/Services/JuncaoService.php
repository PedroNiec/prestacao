<?php
declare(strict_types=1);

namespace App\Services;

use App\Catalog\DocumentCatalog;
use App\Repositories\ArquivoRepository;
use App\Repositories\DocumentoRepository;

/** Regras dos documentos de junção: adicionar, remover, reordenar e gerar o PDF único. */
final class JuncaoService
{
    public function __construct(
        private ArquivoRepository $arquivos,
        private DocumentoRepository $documentos,
        private FileStorage $storage,
        private PdfMergeService $merge,
    ) {
    }

    public static function ehJuncao(string $codigo): bool
    {
        $d = DocumentCatalog::find($codigo);
        return $d !== null && $d['comportamento'] === DocumentCatalog::MERGE;
    }

    /**
     * Adiciona vários arquivos; cada um é validado separadamente.
     * @param array<int,array> $files lista de entradas no formato de $_FILES (já normalizada)
     * @return array{enviados:int,erros:string[]}
     */
    public function adicionar(int $projetoId, string $codigo, array $files): array
    {
        $this->exigirJuncao($codigo);
        $ok = 0;
        $erros = [];
        foreach ($files as $file) {
            $nome = (string) ($file['name'] ?? '');
            if ((int) ($file['error'] ?? 0) === UPLOAD_ERR_NO_FILE && $nome === '') {
                continue;
            }
            try {
                $r = $this->storage->storeUpload($file, DocumentoService::subdir($projetoId) . '/juncao', ['pdf']);
                $this->arquivos->add($projetoId, $codigo, $r['caminho'], $r['nome_original'], $r['tamanho']);
                $ok++;
            } catch (UploadException $e) {
                $erros[] = ($nome !== '' ? "$nome: " : '') . $e->getMessage();
            }
        }
        if ($ok > 0) {
            $this->documentos->markOutdated($projetoId, $codigo);
        }
        return ['enviados' => $ok, 'erros' => $erros];
    }

    public function remover(int $projetoId, string $codigo, int $arquivoId): bool
    {
        $this->exigirJuncao($codigo);
        $a = $this->arquivos->find($projetoId, $codigo, $arquivoId);
        if ($a === null) {
            return false;
        }
        $this->arquivos->delete($arquivoId);
        $this->storage->delete($a['caminho']);
        $this->documentos->markOutdated($projetoId, $codigo);
        return true;
    }

    /**
     * @param int[] $ids nova ordem completa
     * @throws \InvalidArgumentException se não for exatamente o conjunto atual de arquivos
     */
    public function reordenar(int $projetoId, string $codigo, array $ids): void
    {
        $this->exigirJuncao($codigo);
        $atuais = array_map(fn ($a) => (int) $a['id'], $this->arquivos->list($projetoId, $codigo));
        $novos = array_map('intval', $ids);
        $a = $atuais;
        $b = $novos;
        sort($a);
        sort($b);
        if ($a !== $b) {
            throw new \InvalidArgumentException('A nova ordem não corresponde aos arquivos atuais.');
        }
        if ($novos === $atuais) {
            return;
        }
        $this->arquivos->setOrder($projetoId, $codigo, $novos);
        $this->documentos->markOutdated($projetoId, $codigo);
    }

    /** Move um arquivo uma posição para cima (-1) ou para baixo (+1). */
    public function mover(int $projetoId, string $codigo, int $arquivoId, int $direcao): void
    {
        $ids = array_map(fn ($a) => (int) $a['id'], $this->arquivos->list($projetoId, $codigo));
        $i = array_search($arquivoId, $ids, true);
        $j = $i === false ? false : $i + ($direcao < 0 ? -1 : 1);
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        $this->reordenar($projetoId, $codigo, $ids);
    }

    /** @throws PdfMergeException */
    public function gerar(int $projetoId, string $codigo): void
    {
        $this->exigirJuncao($codigo);
        $lista = $this->arquivos->list($projetoId, $codigo);
        if ($lista === []) {
            throw new PdfMergeException('Envie ao menos um PDF antes de gerar.');
        }
        $entrada = array_map(fn ($a) => [
            'path' => $this->storage->absolute($a['caminho']),
            'nome' => $a['nome_original'],
        ], $lista);

        $relativo = DocumentoService::subdir($projetoId) . '/gerados/' . bin2hex(random_bytes(16)) . '.pdf';
        $this->storage->prepareDir(dirname($relativo));
        $this->merge->merge($entrada, $this->storage->absolute($relativo));

        $antigo = $this->documentos->find($projetoId, $codigo);
        $this->documentos->save($projetoId, $codigo, $relativo, DocumentCatalog::find($codigo)['arquivo'], (int) filesize($this->storage->absolute($relativo)));
        if ($antigo !== null && $antigo['caminho'] !== $relativo) {
            $this->storage->delete($antigo['caminho']);
        }
    }

    private function exigirJuncao(string $codigo): void
    {
        if (!self::ehJuncao($codigo)) {
            throw new \InvalidArgumentException('Este documento não é de junção de PDFs.');
        }
    }
}
