<?php
$base = '/projetos/' . (int) $projeto['id'] . '/documentos/' . $doc['codigo'];
$desatualizado = $final && (int) $final['desatualizado'] === 1;
?>
<p><a href="/projetos/<?= (int) $projeto['id'] ?>#doc-<?= e($doc['codigo']) ?>">← Voltar ao projeto #<?= (int) $projeto['id'] ?></a></p>
<div class="barra">
    <h1><?= e($doc['codigo']) ?> — <?= e($doc['nome']) ?></h1>
</div>
<p>Envie os PDFs, arraste para definir a ordem e clique em <strong>Gerar PDF</strong>. Nenhum arquivo é obrigatório.</p>

<h2>PDF final</h2>
<div class="caixa">
    <?php if (!$final): ?>
        <span class="tag vazio">Não gerado</span>
    <?php elseif ($desatualizado): ?>
        <span class="tag aviso-tag">Desatualizado</span>
        A lista de arquivos mudou desde a última geração. Gere novamente.
    <?php else: ?>
        <span class="tag ok">Gerado</span> <small><?= e(format_bytes((int) $final['tamanho'])) ?> · <?= e($final['atualizado_em']) ?></small>
    <?php endif; ?>
    <div class="acoes" style="margin-top:.6rem">
        <form method="post" action="<?= e($base) ?>/gerar">
            <?= csrf_field() ?>
            <button class="btn" type="submit" <?= $arquivos ? '' : 'disabled' ?>>Gerar PDF</button>
        </form>
        <?php if ($final): ?>
            <a href="<?= e($base) ?>/baixar?ver=1" target="_blank" rel="noopener">Ver</a>
            <a href="<?= e($base) ?>/baixar">Baixar</a>
        <?php endif; ?>
    </div>
</div>

<h2>Arquivos (<?= count($arquivos) ?>)</h2>
<?php if (!$arquivos): ?>
    <p class="aviso">Nenhum arquivo enviado ainda.</p>
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
