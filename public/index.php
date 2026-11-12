<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Request;

// Servidor embutido: deixa o PHP servir arquivos estáticos de public/ (css, js).
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $real = realpath(__DIR__ . $path);
    if ($real !== false && is_file($real) && str_starts_with($real, __DIR__ . DIRECTORY_SEPARATOR) && $real !== __FILE__) {
        return false;
    }
}

prepare_storage();
handle(Request::fromGlobals(), $_SERVER['HTTP_HOST'] ?? '')->send();
