<?php
declare(strict_types=1);

namespace App\Services;

use App\Catalog\DocumentCatalog;
use App\Repositories\DocumentoRepository;

/** Regras dos documentos de upload (substituir, remover) e limpeza de arquivos do projeto. */
final class DocumentoService
{
    public function __construct(
        private DocumentoRepository $repo,
        private FileStorage $storage,
        private ?ConversionService $conversor = null,
    ) {
    }

    public static function subdir(int $projetoId): string
    {
        return 'projetos/' . $projetoId;
    }

    /** @throws UploadException */
    public function enviar(int $projetoId, string $codigo, array $file): void
    {
        $doc = DocumentCatalog::find($codigo);
        $converte = DocumentCatalog::ehConversao($codigo);
        if ($doc === null || ($doc['comportamento'] !== DocumentCatalog::UPLOAD && !$converte)) {
            throw new UploadException('Este documento não aceita envio direto de arquivo.');
        }
        $sub = self::subdir($projetoId);
        $original = $this->storage->storeUpload($file, $sub . ($converte ? '/tmp' : ''), DocumentCatalog::extensoesUpload($codigo));

        if ($converte) {
            try {
                $abs = $this->storage->absolute($original['caminho']);
                $bytes = $doc['comportamento'] === DocumentCatalog::CONVERT_PNG
                    ? $this->conversor()->pngToPdf($abs)
                    : $this->conversor()->pptxToPdf($abs);
                $novo = [
                    'caminho' => $this->storage->storeGenerated($bytes, $sub . '/convertidos'),
                    'nome_original' => $original['nome_original'],
                    'tamanho' => strlen($bytes),
                ];
            } finally {
                $this->storage->delete($original['caminho']);
            }
        } else {
            $novo = $original;
        }

        $antigo = $this->repo->find($projetoId, $codigo);
        try {
            $this->repo->save($projetoId, $codigo, $novo['caminho'], $novo['nome_original'], $novo['tamanho']);
        } catch (\Throwable $e) {
            $this->storage->delete($novo['caminho']);
            throw $e;
        }
        if ($antigo !== null && $antigo['caminho'] !== $novo['caminho']) {
            $this->storage->delete($antigo['caminho']);
        }
    }

    private function conversor(): ConversionService
    {
        return $this->conversor ?? throw new UploadException('Conversão indisponível.');
    }

    public function remover(int $projetoId, string $codigo): bool
    {
        $doc = $this->repo->find($projetoId, $codigo);
        if ($doc === null) {
            return false;
        }
        $this->repo->delete($projetoId, $codigo);
        $this->storage->delete($doc['caminho']);
        return true;
    }

    public function removerArquivosDoProjeto(int $projetoId): void
    {
        $this->storage->deleteDir(self::subdir($projetoId));
    }

    /** Caminho absoluto do PDF do documento, ou null se não existir. */
    public function arquivo(int $projetoId, string $codigo): ?array
    {
        $doc = $this->repo->find($projetoId, $codigo);
        if ($doc === null || !$this->storage->exists($doc['caminho'])) {
            return null;
        }
        return ['path' => $this->storage->absolute($doc['caminho']), 'registro' => $doc];
    }
}
