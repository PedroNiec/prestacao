<?php
declare(strict_types=1);

namespace App\Domain;

/** Totais do relatório físico-financeiro (somas simples sobre o que o usuário digitou). */
final class FisicoFinanceiroCalculator
{
    private const SOMAS_FISICAS = ['fis_prog_periodo', 'fis_exec_periodo', 'fis_prog_ate', 'fis_exec_ate'];
    private const SOMAS_FINANCEIRAS = ['fin_conc_periodo', 'fin_exec_periodo', 'fin_rend_periodo', 'fin_conc_ate', 'fin_exec_ate'];

    /** Totais de uma linha: período = concedente + executor + outros rendimentos; até o período = concedente + executor. */
    public static function totaisLinha(array $l): array
    {
        return [
            'total_periodo' => (int) $l['fin_conc_periodo'] + (int) $l['fin_exec_periodo'] + (int) $l['fin_rend_periodo'],
            'total_ate' => (int) $l['fin_conc_ate'] + (int) $l['fin_exec_ate'],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $linhas
     * @return array{
     *   linhas: array<int,array<string,mixed>>,
     *   fisico: ?array<string,mixed>,
     *   financeiro: array<string,int>
     * }
     * 'fisico' é null quando as linhas usam unidades diferentes: somar m² com unidades
     * não faz sentido, então o total físico só existe se a unidade for a mesma.
     */
    public static function totais(array $linhas): array
    {
        $fin = array_fill_keys(self::SOMAS_FINANCEIRAS, 0) + ['total_periodo' => 0, 'total_ate' => 0];
        $fis = array_fill_keys(self::SOMAS_FISICAS, 0);
        $unidades = [];
        $out = [];
        foreach ($linhas as $l) {
            $l += self::totaisLinha($l);
            $out[] = $l;
            foreach (self::SOMAS_FINANCEIRAS as $c) {
                $fin[$c] += (int) $l[$c];
            }
            $fin['total_periodo'] += $l['total_periodo'];
            $fin['total_ate'] += $l['total_ate'];
            foreach (self::SOMAS_FISICAS as $c) {
                $fis[$c] += (int) $l[$c];
            }
            $u = mb_strtolower(trim((string) $l['unidade']));
            if ($u !== '') {
                $unidades[$u] ??= (string) $l['unidade']; // mantém a grafia da primeira linha
            }
        }
        $fisico = count($unidades) <= 1 ? $fis + ['unidade' => $unidades ? reset($unidades) : ''] : null;
        return ['linhas' => $out, 'fisico' => $fisico, 'financeiro' => $fin];
    }
}
