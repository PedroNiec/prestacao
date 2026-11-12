<?php
declare(strict_types=1);

namespace App\Domain;

/** Dinheiro em centavos (inteiros), com leitura/formatação no padrão brasileiro. */
final class Money
{
    /** Limite de sanidade: R$ 9.999.999.999,99 */
    public const MAX_CENTAVOS = 999_999_999_999;

    /**
     * Lê "1.234,56", "1234,56", "1234.56", "R$ 1.234,56" ou "1.234" (milhar). Retorna centavos
     * ou null se for inválido, negativo ou grande demais. Vazio = null.
     */
    public static function parse(?string $texto): ?int
    {
        $t = trim((string) $texto);
        $t = trim(preg_replace('/^R\$\s*/u', '', $t) ?? '');
        if ($t === '' || !preg_match('/^\d[\d.,]*$/', $t)) {
            return null;
        }
        if (str_contains($t, ',')) {
            // vírgula é o decimal; pontos só podem ser separadores de milhar
            if (substr_count($t, ',') !== 1) {
                return null;
            }
            [$int, $dec] = explode(',', $t);
            if (!preg_match('/^\d{1,3}(\.\d{3})*$|^\d+$/', $int) || !preg_match('/^\d{1,2}$/', $dec)) {
                return null;
            }
            $int = str_replace('.', '', $int);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $t)) {
            [$int, $dec] = [str_replace('.', '', $t), '0'];   // 1.234 = mil duzentos e trinta e quatro
        } elseif (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $t, $m)) {
            [$int, $dec] = [$m[1], $m[2] ?? '0'];              // 1234.56 ou 1234
        } else {
            return null;
        }
        if (strlen($int) > 13) {
            return null;
        }
        $centavos = (int) $int * 100 + (int) str_pad($dec, 2, '0');
        return $centavos <= self::MAX_CENTAVOS ? $centavos : null;
    }

    /** 49715249 => "497.152,49" */
    public static function format(int $centavos): string
    {
        $sinal = $centavos < 0 ? '-' : '';
        $abs = abs($centavos);
        return $sinal . number_format(intdiv($abs, 100), 0, ',', '.') . ',' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Como na planilha: zero aparece como "-". */
    public static function formatOrDash(int $centavos): string
    {
        return $centavos === 0 ? '-' : self::format($centavos);
    }

    /** Valor para preencher campo de formulário: "1234,50" (sem milhar). */
    public static function input(int $centavos): string
    {
        return intdiv($centavos, 100) . ',' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Quantidade física (centésimos): 100 => "1"; 123450 => "1.234,50". */
    public static function formatQuantidade(int $centesimos): string
    {
        return $centesimos % 100 === 0
            ? number_format(intdiv($centesimos, 100), 0, ',', '.')
            : self::format($centesimos);
    }
}
