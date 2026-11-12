<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (PHP_SAPI === 'cli') {
            $_SESSION ??= [];
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
        }
    }

    /** Guarda uma mensagem para a próxima página. $tipo: ok | erro */
    public static function flash(string $mensagem, string $tipo = 'ok'): void
    {
        self::start();
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    /** @return array<int,array{tipo:string,mensagem:string}> */
    public static function pullFlash(): array
    {
        self::start();
        $msgs = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $msgs;
    }

    /** Guarda dados digitados para repopular o formulário após erro de validação. */
    public static function old(?array $dados = null): array
    {
        self::start();
        if ($dados !== null) {
            $_SESSION['_old'] = $dados;
            return $dados;
        }
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return $old;
    }
}
