<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function dir(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }

    /** Renderiza templates/<name>.php dentro de templates/layout.php (quando $layout = true). */
    public static function render(string $name, array $data = [], bool $layout = true): string
    {
        $content = self::capture($name, $data);
        if (!$layout) {
            return $content;
        }
        return self::capture('layout', $data + ['content' => $content, 'titulo' => $data['titulo'] ?? 'Prestação de Contas']);
    }

    private static function capture(string $name, array $data): string
    {
        if (!preg_match('/^[a-z0-9_\/-]+$/i', $name)) {
            throw new RuntimeException("Nome de template inválido: $name");
        }
        $file = self::dir() . '/' . $name . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Template não encontrado: $name");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
