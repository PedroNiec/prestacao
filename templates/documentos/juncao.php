<?php
use App\Core\View;

$base = '/projetos/' . (int) $projeto['id'] . '/documentos/' . $doc['codigo'];
?>
<?= View::render("partes/trilha", ["projeto" => $projeto, "ancora" => "#doc-" . $doc["codigo"], "itens" => [[$doc["codigo"] . " " . $doc["nome"]]]], false) ?>
<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1><?= e($doc["codigo"]) ?> — <?= e($doc["nome"]) ?></h1>
        <p class="projeto-sub"><?= e($projeto["municipio_nome"]) ?> - <?= e($projeto["municipio_uf"]) ?> · Projeto #<?= (int) $projeto["id"] ?></p>
    </div>
</div>
<p class="dica">Envie os PDFs, arraste para definir a ordem e clique em <strong>Gerar PDF</strong>. Nenhum arquivo é obrigatório.</p>

<?= View::render("partes/pdf", [
    "final" => $final, "gerar" => $base . "/gerar", "baixar" => $base . "/baixar", "desabilitado" => !$arquivos,
    "textos" => ["vazio" => "Não gerado", "gerado" => "Gerado", "desatualizado" => "A lista de arquivos mudou desde a última geração. Gere novamente."],
], false) ?>

<h2>Arquivos (<?= count($arquivos) ?>)</h2>
<?php if (!$arquivos): ?>
    <div class="estado-vazio"><strong>Nenhum arquivo enviado ainda.</strong> Use "Adicionar PDFs" logo abaixo.</div>
<?php else: ?>
    <form method="post" action="<?= e($base) ?>/arquivos/ordem" id="form-ordem">
        <?= csrf_field() ?>
        <ol class="lista-arquivos" id="lista" data-arrastavel="1">
        <?php foreach ($arquivos as $i => $a): ?>
            <li draggable="true" data-id="<?= (int) $a['id'] ?>">
                <span class="alca" title="Arraste para reordenar" aria-hidden="true">⠿</span>
                <input type="hidden" name="ordem[]" value="<?= (int) $a['id'] ?>">
                <span class="nome"><?= e($a['nome_original']) ?></span>
                <small><?= e(format_bytes((int) $a['tamanho'])) ?></small>
                <a href="<?= e($base) ?>/arquivos/<?= (int) $a['id'] ?>/ver" target="_blank" rel="noopener">Ver</a>
                <button class="link" type="submit" form="mover-<?= (int) $a['id'] ?>-cima" <?= $i === 0 ? 'disabled' : '' ?> aria-label="Mover para cima">↑</button>
                <button class="link" type="submit" form="mover-<?= (int) $a['id'] ?>-baixo" <?= $i === count($arquivos) - 1 ? 'disabled' : '' ?> aria-label="Mover para baixo">↓</button>
                <button class="link perigo" type="submit" form="remover-<?= (int) $a['id'] ?>" onclick="return confirm('Remover este arquivo?')">Remover</button>
            </li>
        <?php endforeach; ?>
        </ol>
        <noscript><button class="btn secundario" type="submit">Salvar ordem</button></noscript>
    </form>
    <?php foreach ($arquivos as $a): $aid = (int) $a['id']; ?>
        <form method="post" id="mover-<?= $aid ?>-cima" action="<?= e($base) ?>/arquivos/<?= $aid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="cima"></form>
        <form method="post" id="mover-<?= $aid ?>-baixo" action="<?= e($base) ?>/arquivos/<?= $aid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="baixo"></form>
        <form method="post" id="remover-<?= $aid ?>" action="<?= e($base) ?>/arquivos/<?= $aid ?>/remover"><?= csrf_field() ?></form>
    <?php endforeach; ?>
<?php endif; ?>

<h2>Adicionar PDFs</h2>
<form method="post" action="<?= e($base) ?>/arquivos" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>
    <label>Selecione um ou mais PDFs (até 50 MB cada)
        <input type="file" name="arquivos[]" accept="application/pdf,.pdf" multiple required>
    </label>
    <button class="btn" type="submit">Enviar</button>
</form>
<script src="/assets/juncao.js"></script>
