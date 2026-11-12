<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        Session::start();
        return $_SESSION[self::KEY] ??= bin2hex(random_bytes(32));
    }

    public static function valid(?string $token): bool
    {
        Session::start();
        $expected = $_SESSION[self::KEY] ?? '';
        return is_string($token) && $expected !== '' && hash_equals($expected, $token);
    }
}
