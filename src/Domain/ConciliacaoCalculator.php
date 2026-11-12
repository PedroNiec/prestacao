<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * Cálculos da conciliação bancária (doc. 15).
 *
 * Decisões do usuário: receitas vêm do balancete (editáveis); notas fiscais são digitadas;
 * "Total de despesas" soma SOMENTE o bruto das notas (devoluções ficam fora do total).
 */
final class ConciliacaoCalculator
{
    public const MIN_SLOTS = 3;   // a planilha sempre mostra 3 repasses e 3 contrapartidas

    public const DEVOLUCOES = [
        'dev_conc_rend' => 'Devolução Concedente Rendimento de Aplicação',
        'dev_conc_indevido' => 'Devolução Concedente Pagamento Indevido',
        'dev_conc_tarifas' => 'Devolução Concedente Ressarcimento de Tarifas',
        'dev_conv_rend' => 'Devolução Convenente Rendimentos de Aplicação',
        'dev_conv_saldo' => 'Devolução Convenente Saldo Remanescente',
    ];

    /**
     * Receitas calculadas a partir dos lançamentos do balancete.
     * @param array<int,array<string,mixed>> $lancamentos
     * @return array<int,array{chave:string,rotulo:string,calculado:int}>
     */
    public static function receitasCalculadas(array $lancamentos): array
    {
        $rep = $cp = [];
        $rend = 0;
        foreach ($lancamentos as $l) {
            if ($l['tipo'] !== 'receita') {
                continue;
            }
            $v = (int) $l['valor_centavos'];
            match ($l['origem']) {
                'convenio' => $rep[] = ['id' => (int) $l['id'], 'v' => $v],
                'contrapartida' => $cp[] = ['id' => (int) $l['id'], 'v' => $v],
                default => $rend += $v,
            };
        }
        $out = [];
        foreach ([['rep', 'º Repasse', $rep], ['cp', 'ª Contrapartida', $cp]] as [$pref, $sufixo, $itens]) {
            $total = max(self::MIN_SLOTS, count($itens));
            for ($i = 0; $i < $total; $i++) {
                $item = $itens[$i] ?? null;
                $out[] = [
                    'chave' => $item ? "$pref-{$item['id']}" : "$pref-slot-" . ($i + 1),
                    'rotulo' => ($i + 1) . $sufixo,
                    'calculado' => $item['v'] ?? 0,
                ];
            }
        }
        $out[] = ['chave' => 'rend', 'rotulo' => 'Rendimentos Aplicação Financeira', 'calculado' => $rend];
        return $out;
    }

    /**
     * Aplica as sobrescritas do usuário.
     * @param array<int,array{chave:string,rotulo:string,calculado:int}> $linhas
     * @param array<string,int> $overrides chave => centavos
     * @return array<int,array{chave:string,rotulo:string,calculado:int,valor:int,alterado:bool}>
     */
    public static function aplicarOverrides(array $linhas, array $overrides): array
    {
        return array_map(function (array $l) use ($overrides) {
            $tem = array_key_exists($l['chave'], $overrides);
            $valor = $tem ? $overrides[$l['chave']] : $l['calculado'];
            return $l + ['valor' => $valor, 'alterado' => $tem && $valor !== $l['calculado']];
        }, $linhas);
    }

    /**
     * Linhas de um bloco de nota fiscal, com a coluna SOMA como na planilha
     * (líquido + retenções, acumulando até chegar ao bruto).
     * @return array<int,array{rotulo:string,valor:int,soma:int,destaque:bool}>
     */
    public static function blocoNota(array $n): array
    {
        $bruto = (int) $n['bruto_centavos'];
        $ir = (int) $n['ir_centavos'];
        $iss = (int) $n['iss_centavos'];
        $inss = (int) $n['inss_centavos'];
        $outros = (int) $n['outros_centavos'];
        $liquido = $bruto - $ir - $iss - $inss - $outros;
        $nf = trim((string) $n['nota_fiscal']);

        $linhas = [
            ['rotulo' => 'Nota Fiscal nº ' . $nf, 'valor' => $bruto, 'soma' => $bruto, 'destaque' => true],
            ['rotulo' => 'Nota Fiscal - Valor Líquido', 'valor' => $liquido, 'soma' => $liquido, 'destaque' => false],
        ];
        $soma = $liquido;
        foreach ([['IR sobre Nota Fiscal', $ir], ['ISS sobre Nota Fiscal', $iss], ['INSS sobre Nota Fiscal', $inss], ['Outros descontos', $outros]] as [$rotulo, $v]) {
            if ($rotulo === 'Outros descontos' && $v === 0) {
                continue; // linha opcional
            }
            $soma += $v;
            $linhas[] = ['rotulo' => $rotulo, 'valor' => $v, 'soma' => $soma, 'destaque' => false];
        }
        return $linhas;
    }

    public static function liquido(array $n): int
    {
        return (int) $n['bruto_centavos'] - (int) $n['ir_centavos'] - (int) $n['iss_centavos'] - (int) $n['inss_centavos'] - (int) $n['outros_centavos'];
    }

    /**
     * @param array<int,array<string,mixed>> $receitas linhas já com 'valor'
     * @param array<int,array<string,mixed>> $notas
     * @param array<string,mixed> $config
     * @return array<string,int>
     */
    public static function totais(array $receitas, array $notas, array $config): array
    {
        $totalReceitas = array_sum(array_column($receitas, 'valor'));
        $totalDespesas = array_sum(array_map(fn ($n) => (int) $n['bruto_centavos'], $notas)); // só notas (decisão do usuário)
        $saldo = $totalReceitas - $totalDespesas;
        $bancario = (int) $config['saldo_cc'] + (int) $config['saldo_aplic'];
        $devolucoes = 0;
        foreach (array_keys(self::DEVOLUCOES) as $k) {
            $devolucoes += (int) $config[$k];
        }
        return [
            'receitas' => $totalReceitas, 'despesas' => $totalDespesas, 'saldo' => $saldo,
            'bancario' => $bancario, 'conciliacao' => $saldo - $bancario, 'devolucoes' => $devolucoes,
        ];
    }
}
