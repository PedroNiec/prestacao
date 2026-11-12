<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function path(): string
    {
        return getenv('PRESTACAO_DB') ?: dirname(__DIR__, 2) . '/storage/database.sqlite';
    }

    public static function connection(): PDO
    {
        return self::$pdo ??= self::open(self::path());
    }

    public static function open(string $path): PDO
    {
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        return $pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
