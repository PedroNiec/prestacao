<?php
$base = '/projetos/' . (int) $projeto['id'];
$v = fn (string $k) => e($n[$k] ?? '');
?>
<?= \App\Core\View::render("partes/trilha", ["projeto" => $projeto, "ancora" => "#doc-15", "itens" => [["15 Conciliação bancária", $base . "/conciliacao#notas"], [$titulo]]], false) ?>
<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="linha">
        <label>Nº da nota fiscal <input name="nota_fiscal" required maxlength="40" value="<?= $v('nota_fiscal') ?>" autofocus></label>
        <label>Valor bruto (R$) <input name="bruto" required inputmode="decimal" placeholder="0,00" value="<?= $v('bruto') ?>"></label>
    </div>
    <fieldset>
        <legend>Retenções e descontos (R$) <small>(em branco = 0)</small></legend>
        <div class="linha">
            <label>IR <input name="ir" inputmode="decimal" placeholder="0,00" value="<?= $v('ir') ?>"></label>
            <label>ISS <input name="iss" inputmode="decimal" placeholder="0,00" value="<?= $v('iss') ?>"></label>
            <label>INSS <input name="inss" inputmode="decimal" placeholder="0,00" value="<?= $v('inss') ?>"></label>
            <label>Outros descontos <input name="outros" inputmode="decimal" placeholder="0,00" value="<?= $v('outros') ?>"></label>
        </div>
        <p class="dica">Valor líquido = bruto − IR − ISS − INSS − outros descontos (calculado automaticamente).</p>
    </fieldset>
    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn secundario" href="<?= e($base) ?>/conciliacao#notas">Cancelar</a>
    </div>
</form>
