<?php
// Item ativo do menu, pelo caminho da requisição (valor padrão quando não há requisição, ex.: testes em CLI).
$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$ativo = fn (string $prefixo): bool => $caminho === $prefixo || str_starts_with($caminho, $prefixo . '/');
$projetosAtivo = $caminho === '/' || $ativo('/projetos');
$mensagens = \App\Core\Session::pullFlash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> — Prestação de Contas</title>
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<a class="pular-link" href="#conteudo">Ir para o conteúdo</a>
<div class="app">
<header class="lateral">
    <a class="marca" href="/">Prestação de Contas</a>
    <nav aria-label="Principal">
        <a href="/projetos"<?= $projetosAtivo ? ' aria-current="page"' : '' ?>>Projetos</a>
        <a href="/municipios"<?= $ativo('/municipios') ? ' aria-current="page"' : '' ?>>Municípios</a>
    </nav>
    <a class="lateral-rodape" href="/guia-visual">Guia visual</a>
</header>
<div class="app-principal">
<main class="conteudo" id="conteudo" tabindex="-1">
    <?php if ($mensagens): ?>
        <div class="alertas" role="region" aria-label="Mensagens">
        <?php foreach ($mensagens as $f): ?>
            <div class="msg msg-<?= e($f['tipo']) ?>" role="<?= $f['tipo'] === 'erro' ? 'alert' : 'status' ?>">
                <span class="msg-texto"><?= e($f['mensagem']) ?></span>
                <button type="button" class="msg-fechar" aria-label="Fechar mensagem">&times;</button>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
<footer class="rodape">
    Prestação de Contas · uso local · <a href="/guia-visual">Guia visual</a>
</footer>
</div>
</div>
</body>
</html>
