<?php
declare(strict_types=1);

namespace App\Domain;

/** Totais do balancete a partir dos lançamentos (tudo em centavos). */
final class BalanceteCalculator
{
    public const ORIGENS = [
        'convenio' => 'Convênio',
        'rendimentos' => 'Rendimentos da aplicação financeira',
        'contrapartida' => 'Contrapartida',
    ];

    /**
     * @param array<int,array<string,mixed>> $lancamentos
     * @return array{
     *   receita:int, despesa:int, saldo:int,
     *   origens: array<string,array{receita:int,despesa:int}>
     * }
     */
    public static function totais(array $lancamentos): array
    {
        $origens = array_fill_keys(array_keys(self::ORIGENS), ['receita' => 0, 'despesa' => 0]);
        $receita = $despesa = 0;
        foreach ($lancamentos as $l) {
            $v = (int) $l['valor_centavos'];
            $tipo = $l['tipo'] === 'receita' ? 'receita' : 'despesa';
            $origens[$l['origem']][$tipo] += $v;
            $tipo === 'receita' ? $receita += $v : $despesa += $v;
        }
        return ['receita' => $receita, 'despesa' => $despesa, 'saldo' => $receita - $despesa, 'origens' => $origens];
    }

    /** Fonte sugerida pela origem (RN-08): contrapartida = 2 (Convenente); demais = 1 (Concedente). */
    public static function fontePadrao(string $origem): int
    {
        return $origem === 'contrapartida' ? 2 : 1;
    }
}
