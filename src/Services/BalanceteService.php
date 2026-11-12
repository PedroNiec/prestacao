<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\View;
use App\Domain\BalanceteCalculator;
use App\Domain\Validator;
use App\Repositories\DocumentoRepository;
use App\Repositories\LancamentoRepository;
use App\Repositories\ProjetoRepository;

/** Regras do balancete (doc. 01): lançamentos, invalidação dos relatórios derivados e PDF. */
final class BalanceteService
{
    public const CODIGO = '01';
    /**
     * Documentos cujos valores dependem dos lançamentos (conciliação e bens).
     * O físico-financeiro (22) NÃO depende: seus valores são sempre digitados (decisão do usuário).
     */
    public const DEPENDENTES = ['01', '09', '15'];

    public function __construct(
        private LancamentoRepository $lancamentos,
        private DocumentoRepository $documentos,
        private ProjetoRepository $projetos,
        private FileStorage $storage,
        private ReportPdfService $pdf,
    ) {
    }

    public function criar(int $projetoId, array $dados): int
    {
        $id = $this->lancamentos->create($projetoId, $dados);
        $this->invalidar($projetoId);
        return $id;
    }

    public function atualizar(int $projetoId, int $id, array $dados): void
    {
        $this->lancamentos->update($projetoId, $id, $dados);
        $this->invalidar($projetoId);
    }

    public function excluir(int $projetoId, int $id): void
    {
        $this->lancamentos->delete($projetoId, $id);
        $this->invalidar($projetoId);
    }

    public function mover(int $projetoId, int $id, int $direcao): void
    {
        if ($this->lancamentos->move($projetoId, $id, $direcao)) {
            $this->invalidar($projetoId);
        }
    }

    /** O PDF já gerado deixa de refletir os dados: marca como desatualizado. */
    private function invalidar(int $projetoId): void
    {
        foreach (self::DEPENDENTES as $codigo) {
            $this->documentos->markOutdated($projetoId, $codigo);
        }
    }

    /** Dados usados pelo template do PDF e pela tela. */
    public function dados(int $projetoId): array
    {
        $projeto = $this->projetos->find($projetoId) ?? throw new \RuntimeException('Projeto não encontrado.');
        $lista = $this->lancamentos->list($projetoId);
        return ['projeto' => $projeto, 'lancamentos' => $lista, 'totais' => BalanceteCalculator::totais($lista)];
    }

    /** @throws \RuntimeException */
    public function gerar(int $projetoId): void
    {
        $dados = $this->dados($projetoId);
        $html = View::render('pdf/balancete', $dados, false);
        $bytes = $this->pdf->render($html, 'landscape');

        $sub = DocumentoService::subdir($projetoId) . '/gerados';
        $novo = $this->storage->storeGenerated($bytes, $sub);
        $antigo = $this->documentos->find($projetoId, self::CODIGO);
        $this->documentos->save($projetoId, self::CODIGO, $novo, \App\Catalog\DocumentCatalog::find(self::CODIGO)['arquivo'], strlen($bytes));
        if ($antigo !== null && $antigo['caminho'] !== $novo) {
            $this->storage->delete($antigo['caminho']);
        }
    }
}
