<?php
/**
 * Trilha de navegação (spec melhoria-visual-demais-telas, RF-T01).
 * $projeto (opcional): acrescenta "Projetos › <objeto>"; $ancora (opcional) leva à linha do documento (#doc-XX).
 * $itens: list<array{0:string, 1?:?string}> — rótulo e URL; o último item é a página atual (sem link).
 */
$lista = [];
if (isset($projeto)) {
    $pid = (int) $projeto['id'];
    $objeto = trim((string) ($projeto['objeto'] ?? ''));
    $lista[] = ['Projetos', '/projetos', false];
    $lista[] = [$objeto !== '' ? $objeto : 'Projeto #' . $pid, '/projetos/' . $pid . ($ancora ?? ''), true];
}
foreach ($itens ?? [] as $it) {
    $lista[] = [$it[0], $it[1] ?? null, false];
}
$ultimo = count($lista) - 1;
?>
<nav aria-label="Trilha de navegação">
    <ol class="migalhas">
<?php foreach ($lista as $i => [$rotulo, $url, $ehObjeto]):
    $attr = $ehObjeto ? ' class="trilha-objeto" title="' . e($rotulo) . '"' : ''; ?>
        <li<?= $i === $ultimo ? ' aria-current="page"' : '' ?>><?php if ($i !== $ultimo && $url !== null): ?><a href="<?= e($url) ?>"<?= $attr ?>><?= e($rotulo) ?></a><?php else: ?><span<?= $attr ?>><?= e($rotulo) ?></span><?php endif; ?></li>
<?php endforeach; ?>
    </ol>
</nav>
