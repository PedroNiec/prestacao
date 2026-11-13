<?php
declare(strict_types=1);

namespace App\Presenters;

/** Iniciais de um nome para o "avatar" dos cartões (spec redesenho-layout-municipios, RF-M02 e EX-L01). */
final class Iniciais
{
    private const IGNORAR = ['da', 'das', 'de', 'do', 'dos', 'e'];

    public static function de(string $nome): string
    {
        $palavras = array_values(array_filter(
            preg_split('/\s+/u', trim($nome)) ?: [],
            fn (string $p) => $p !== '' && !in_array(mb_strtolower($p), self::IGNORAR, true)
        ));
        if ($palavras === []) {
            return '?';
        }
        if (count($palavras) === 1) {
            return mb_strtoupper(mb_substr($palavras[0], 0, 2));
        }
        return mb_strtoupper(mb_substr($palavras[0], 0, 1) . mb_substr($palavras[count($palavras) - 1], 0, 1));
    }
}
