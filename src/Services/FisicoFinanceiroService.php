<?php
declare(strict_types=1);

namespace App\Services;

use App\Catalog\DocumentCatalog;
use App\Core\View;
use App\Domain\FisicoFinanceiroCalculator;
use App\Repositories\DocumentoRepository;
use App\Repositories\FisicoFinanceiroRepository;
use App\Repositories\ProjetoRepository;

/** Regras do relatório de execução físico-financeiro (doc. 22). Tudo é digitado pelo usuário. */
final class FisicoFinanceiroService
{
    public const CODIGO = '22';

    public function __construct(
        private FisicoFinanceiroRepository $linhas,
        private DocumentoRepository $documentos,
        private ProjetoRepository $projetos,
        private FileStorage $storage,
        private ReportPdfService $pdf,
    ) {
    }

    public function criar(int $projetoId, array $d): int
    {
        $id = $this->linhas->create($projetoId, $d);
        $this->invalidar($projetoId);
        return $id;
    }

    public function atualizar(int $projetoId, int $id, array $d): void
    {
        $this->linhas->update($projetoId, $id, $d);
        $this->invalidar($projetoId);
    }

    public function excluir(int $projetoId, int $id): void
    {
        $this->linhas->delete($projetoId, $id);
        $this->invalidar($projetoId);
    }

    public function mover(int $projetoId, int $id, int $direcao): void
    {
        if ($this->linhas->move($projetoId, $id, $direcao)) {
            $this->invalidar($projetoId);
        }
    }

    private function invalidar(int $projetoId): void
    {
        $this->documentos->markOutdated($projetoId, self::CODIGO);
    }

    public function dados(int $projetoId): array
    {
        $projeto = $this->projetos->find($projetoId) ?? throw new \RuntimeException('Projeto não encontrado.');
        return ['projeto' => $projeto] + FisicoFinanceiroCalculator::totais($this->linhas->list($projetoId));
    }

    /** @throws \RuntimeException */
    public function gerar(int $projetoId): void
    {
        $bytes = $this->pdf->render(View::render('pdf/fisico_financeiro', $this->dados($projetoId), false), 'landscape');
        $novo = $this->storage->storeGenerated($bytes, DocumentoService::subdir($projetoId) . '/gerados');
        $antigo = $this->documentos->find($projetoId, self::CODIGO);
        $this->documentos->save($projetoId, self::CODIGO, $novo, DocumentCatalog::find(self::CODIGO)['arquivo'], strlen($bytes));
        if ($antigo !== null && $antigo['caminho'] !== $novo) {
            $this->storage->delete($antigo['caminho']);
        }
    }
}
