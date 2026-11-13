<?php
$base = '/projetos/' . (int) $projeto['id'];
$rb = $base . '/relacao-de-bens';
$v = fn (string $k) => e($b[$k] ?? '');
?>
<?= \App\Core\View::render('partes/trilha', ['projeto' => $projeto, 'ancora' => '#doc-09', 'itens' => [['09 Relação de bens', $rb], [$titulo]]], false) ?>
<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <label>Descrição
        <textarea name="descricao" rows="3" required maxlength="500" autofocus><?= $v('descricao') ?></textarea>
    </label>
    <?php if (!$edicao && ($b['descricao'] ?? '') !== '' && !isset($b['valor'])): ?>
        <p class="dica">Sugestão: o objeto do projeto. Ajuste se o bem for outro.</p>
    <?php endif; ?>
    <label>Valor (R$)
        <input name="valor" required inputmode="decimal" placeholder="1.234,56" value="<?= $v('valor') ?>">
    </label>
    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn secundario" href="<?= e($rb) ?>">Cancelar</a>
    </div>
</form>
