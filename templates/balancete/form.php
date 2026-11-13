<?php
// Página de EDIÇÃO de lançamento. O novo lançamento é feito na própria tela do balancete
// (specs/balancete-lancamento-na-tela.md).
use App\Core\View;

$base = '/projetos/' . (int) $projeto['id'];
?>
<?= View::render('partes/trilha', ['projeto' => $projeto, 'ancora' => '#doc-01', 'itens' => [['01 Balancete', $base . '/balancete'], [$titulo]]], false) ?>
<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form" id="form-lancamento">
    <?= csrf_field() ?>
    <?= View::render('balancete/_campos', ['l' => $l, 'origens' => $origens, 'autofocus' => true], false) ?>
    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn secundario" href="<?= e($base) ?>/balancete">Cancelar</a>
    </div>
</form>
<script src="/assets/lancamento-form.js"></script>
