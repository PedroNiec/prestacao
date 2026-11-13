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
        return self::capture('layout', $data + [
            'content' => $content,
            'titulo' => $data['titulo'] ?? 'Prestação de Contas',
            'cssArea' => self::areaCss($name),
        ]);
    }

    /**
     * CSS da área do template: public/assets/<área>.css, onde <área> é o primeiro segmento do nome
     * (projetos/index → projetos; guia_visual → guia-visual). Devolve null se o arquivo não existir
     * (specs/css-por-area.md). O valor vem do nome do template, nunca da entrada do usuário.
     */
    public static function areaCss(string $template): ?string
    {
        $area = str_replace('_', '-', explode('/', $template)[0]);
        if ($area === 'app' || !preg_match('/^[a-z0-9-]+$/', $area)) {
            return null;
        }
        return is_file(dirname(__DIR__, 2) . '/public/assets/' . $area . '.css') ? $area : null;
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
