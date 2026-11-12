<?php
declare(strict_types=1);

namespace App\Domain;

final class Dates
{
    private const MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    /** "2026-03-05" => "05/03/2026"; inválido/vazio => "" */
    public static function br(string $iso): string
    {
        $d = self::parse($iso);
        return $d ? $d->format('d/m/Y') : '';
    }

    /** "2026-03-05" => "05 de março de 2026" (independe de locale do servidor) */
    public static function extenso(string $iso): string
    {
        $d = self::parse($iso);
        return $d ? sprintf('%s de %s de %s', $d->format('d'), self::MESES[(int) $d->format('n') - 1], $d->format('Y')) : '';
    }

    public static function parse(string $iso): ?\DateTimeImmutable
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $iso);
        return $d !== false && $d->format('Y-m-d') === $iso ? $d : null;
    }
}
