<?php
use App\Domain\ConciliacaoCalculator;

$base = '/projetos/' . (int) $projeto['id'];
$v = fn (string $k) => e($r[$k] ?? '');
?>
<?= \App\Core\View::render("partes/trilha", ["projeto" => $projeto, "ancora" => "#doc-15", "itens" => [["15 Conciliação bancária", $base . "/conciliacao#receitas"], [$titulo]]], false) ?>
<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="linha">
        <label>Tipo
            <select name="tipo" required autofocus>
                <option value="">Escolha…</option>
                <?php foreach (ConciliacaoCalculator::TIPOS as $k => $nome): ?>
                    <option value="<?= e($k) ?>"<?= ($r['tipo'] ?? '') === $k ? ' selected' : '' ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Valor (R$) <input name="valor" required inputmode="decimal" placeholder="0,00" value="<?= $v('valor') ?>"></label>
    </div>
    <label>Descrição <small>(opcional)</small>
        <input name="descricao" maxlength="200" placeholder="Ex.: Parcela 1 - OB 123" value="<?= $v('descricao') ?>">
    </label>
    <p class="dica">No PDF a linha aparece como «Tipo - descrição».</p>
    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn secundario" href="<?= e($base) ?>/conciliacao#receitas">Cancelar</a>
    </div>
</form>
