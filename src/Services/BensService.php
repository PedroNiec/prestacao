<?php
declare(strict_types=1);

namespace App\Services;

use App\Catalog\DocumentCatalog;
use App\Core\View;
use App\Repositories\BensRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProjetoRepository;

/** Regras da relação de bens (doc. 09). Tudo é digitado pelo usuário (specs/relacao-de-bens.md). */
final class BensService
{
    public const CODIGO = '09';
    public const TITULO_PADRAO = 'RELAÇÃO DE BENS ADQUIRIDOS';

    public function __construct(
        private BensRepository $bens,
        private DocumentoRepository $documentos,
        private ProjetoRepository $projetos,
        private FileStorage $storage,
        private ReportPdfService $pdf,
    ) {
    }

    public function criar(int $projetoId, array $d): int
    {
        $id = $this->bens->create($projetoId, $d);
        $this->invalidar($projetoId);
        return $id;
    }

    public function atualizar(int $projetoId, int $id, array $d): void
    {
        $this->bens->update($projetoId, $id, $d);
        $this->invalidar($projetoId);
    }

    public function excluir(int $projetoId, int $id): void
    {
        $this->bens->delete($projetoId, $id);
        $this->invalidar($projetoId);
    }

    public function mover(int $projetoId, int $id, int $direcao): void
    {
        if ($this->bens->move($projetoId, $id, $direcao)) {
            $this->invalidar($projetoId);
        }
    }

    /** Título vazio volta ao padrão (EX-B03). */
    public function salvarTitulo(int $projetoId, string $titulo): void
    {
        $titulo = trim($titulo);
        $this->bens->salvarTitulo($projetoId, $titulo === self::TITULO_PADRAO ? '' : $titulo);
        $this->invalidar($projetoId);
    }

    private function invalidar(int $projetoId): void
    {
        $this->documentos->markOutdated($projetoId, self::CODIGO);
    }

    /** @return array{projeto:array,bens:array,total:int,tituloDocumento:string,tituloSalvo:string} */
    public function dados(int $projetoId): array
    {
        $projeto = $this->projetos->find($projetoId) ?? throw new \RuntimeException('Projeto não encontrado.');
        $bens = $this->bens->list($projetoId);
        $salvo = $this->bens->titulo($projetoId);
        return [
            'projeto' => $projeto,
            'bens' => $bens,
            'total' => array_sum(array_map(fn ($b) => (int) $b['valor_centavos'], $bens)),
            'tituloDocumento' => $salvo !== '' ? $salvo : self::TITULO_PADRAO,
            'tituloSalvo' => $salvo,
        ];
    }

    /** @throws \RuntimeException */
    public function gerar(int $projetoId): void
    {
        $bytes = $this->pdf->render(View::render('pdf/relacao_bens', $this->dados($projetoId), false), 'portrait');
        $novo = $this->storage->storeGenerated($bytes, DocumentoService::subdir($projetoId) . '/gerados');
        $antigo = $this->documentos->find($projetoId, self::CODIGO);
        $this->documentos->save($projetoId, self::CODIGO, $novo, DocumentCatalog::find(self::CODIGO)['arquivo'], strlen($bytes));
        if ($antigo !== null && $antigo['caminho'] !== $novo) {
            $this->storage->delete($antigo['caminho']);
        }
    }
}
