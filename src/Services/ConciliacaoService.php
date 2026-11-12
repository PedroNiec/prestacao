<?php
declare(strict_types=1);

namespace App\Services;

use App\Catalog\DocumentCatalog;
use App\Core\View;
use App\Domain\ConciliacaoCalculator;
use App\Domain\Money;
use App\Repositories\ConciliacaoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\LancamentoRepository;
use App\Repositories\OverrideRepository;
use App\Repositories\ProjetoRepository;

/** Regras da conciliação bancária (doc. 15). */
final class ConciliacaoService
{
    public const CODIGO = '15';
    public const RELATORIO = 'conciliacao';
    public const TITULO_PADRAO = 'PRESTAÇÃO DE CONTAS FINAL - ENCERRAMENTO DE CONVÊNIO';
    public const ROTULO_PADRAO = 'VALOR - R$';

    public function __construct(
        private ConciliacaoRepository $repo,
        private OverrideRepository $overrides,
        private LancamentoRepository $lancamentos,
        private DocumentoRepository $documentos,
        private ProjetoRepository $projetos,
        private FileStorage $storage,
        private ReportPdfService $pdf,
    ) {
    }

    /** Tudo que a tela e o PDF precisam. */
    public function dados(int $projetoId): array
    {
        $projeto = $this->projetos->find($projetoId) ?? throw new \RuntimeException('Projeto não encontrado.');
        $calculadas = ConciliacaoCalculator::receitasCalculadas($this->lancamentos->list($projetoId));
        $receitas = ConciliacaoCalculator::aplicarOverrides($calculadas, $this->overrides->all($projetoId, self::RELATORIO));
        $notas = $this->repo->notas($projetoId);
        $config = $this->config($projetoId, $projeto);
        return [
            'projeto' => $projeto,
            'receitas' => $receitas,
            'notas' => array_map(fn ($n) => $n + ['liquido' => ConciliacaoCalculator::liquido($n), 'bloco' => ConciliacaoCalculator::blocoNota($n)], $notas),
            'config' => $config,
            'totais' => ConciliacaoCalculator::totais($receitas, $notas, $config),
        ];
    }

    /** Configuração salva ou os padrões (título, rótulo e data do saldo = data do projeto). */
    public function config(int $projetoId, ?array $projeto = null): array
    {
        $projeto ??= $this->projetos->find($projetoId);
        $salva = $this->repo->config($projetoId);
        if ($salva !== null) {
            return $salva;
        }
        $zeros = array_fill_keys(['saldo_cc', 'saldo_aplic', ...array_keys(ConciliacaoCalculator::DEVOLUCOES)], 0);
        return $zeros + [
            'projeto_id' => $projetoId, 'titulo' => self::TITULO_PADRAO, 'rotulo_extrato' => self::ROTULO_PADRAO,
            'extrato_descricao' => '', 'data_saldo' => (string) ($projeto['data_assinatura'] ?? ''),
        ];
    }

    /**
     * Salva as receitas digitadas: valor igual ao calculado remove a sobrescrita; diferente grava.
     * @param array<string,string> $valores chave => texto digitado
     * @return string[] erros
     */
    public function salvarReceitas(int $projetoId, array $valores): array
    {
        $linhas = ConciliacaoCalculator::receitasCalculadas($this->lancamentos->list($projetoId));
        $erros = [];
        $novos = [];
        foreach ($linhas as $l) {
            if (!array_key_exists($l['chave'], $valores)) {
                continue;
            }
            $raw = trim((string) $valores[$l['chave']]);
            $v = $raw === '' ? $l['calculado'] : Money::parse($raw);
            if ($v === null) {
                $erros[] = "Valor inválido em «{$l['rotulo']}» (use números positivos, ex.: 1.234,56).";
                continue;
            }
            $novos[$l['chave']] = $v;
        }
        if ($erros) {
            return $erros;
        }
        foreach ($linhas as $l) {
            if (!isset($novos[$l['chave']])) {
                continue;
            }
            $novos[$l['chave']] === $l['calculado']
                ? $this->overrides->delete($projetoId, self::RELATORIO, $l['chave'])
                : $this->overrides->set($projetoId, self::RELATORIO, $l['chave'], $novos[$l['chave']]);
        }
        $this->overrides->pruneExcept($projetoId, self::RELATORIO, array_column($linhas, 'chave'));
        $this->invalidar($projetoId);
        return [];
    }

    public function restaurar(int $projetoId, string $chave): bool
    {
        $validas = array_column(ConciliacaoCalculator::receitasCalculadas($this->lancamentos->list($projetoId)), 'chave');
        if (!in_array($chave, $validas, true)) {
            return false;
        }
        $this->overrides->delete($projetoId, self::RELATORIO, $chave);
        $this->invalidar($projetoId);
        return true;
    }

    public function salvarConfig(int $projetoId, array $dados): void
    {
        $this->repo->saveConfig($projetoId, $dados);
        $this->invalidar($projetoId);
    }

    public function criarNota(int $projetoId, array $d): int
    {
        $id = $this->repo->createNota($projetoId, $d);
        $this->invalidar($projetoId);
        return $id;
    }

    public function atualizarNota(int $projetoId, int $id, array $d): void
    {
        $this->repo->updateNota($projetoId, $id, $d);
        $this->invalidar($projetoId);
    }

    public function excluirNota(int $projetoId, int $id): void
    {
        $this->repo->deleteNota($projetoId, $id);
        $this->invalidar($projetoId);
    }

    public function moverNota(int $projetoId, int $id, int $direcao): void
    {
        if ($this->repo->moveNota($projetoId, $id, $direcao)) {
            $this->invalidar($projetoId);
        }
    }

    private function invalidar(int $projetoId): void
    {
        $this->documentos->markOutdated($projetoId, self::CODIGO);
    }

    /** @throws \RuntimeException */
    public function gerar(int $projetoId): void
    {
        $bytes = $this->pdf->render(View::render('pdf/conciliacao', $this->dados($projetoId), false), 'portrait');
        $novo = $this->storage->storeGenerated($bytes, DocumentoService::subdir($projetoId) . '/gerados');
        $antigo = $this->documentos->find($projetoId, self::CODIGO);
        $this->documentos->save($projetoId, self::CODIGO, $novo, DocumentCatalog::find(self::CODIGO)['arquivo'], strlen($bytes));
        if ($antigo !== null && $antigo['caminho'] !== $novo) {
            $this->storage->delete($antigo['caminho']);
        }
    }
}
