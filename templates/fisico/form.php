<?php
$base = '/projetos/' . (int) $projeto['id'];
$v = fn (string $k) => e($l[$k] ?? '');
?>
<p><a href="<?= e($base) ?>/fisico-financeiro">← Voltar ao físico-financeiro</a></p>
<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="linha">
        <label>Meta <input name="meta" maxlength="20" value="<?= $v('meta') ?>"></label>
        <label>Etapa / Fase <input name="etapa" maxlength="20" value="<?= $v('etapa') ?>"></label>
        <label>Unidade <input name="unidade" maxlength="20" placeholder="Unid, m², km…" value="<?= $v('unidade') ?>"></label>
    </div>
    <label>Descrição
        <textarea name="descricao" rows="2" required maxlength="500"><?= $v('descricao') ?></textarea>
    </label>

    <fieldset>
        <legend>Físico <small>(em branco = 0)</small></legend>
        <div class="linha">
            <label>No período — programado <input name="fis_prog_periodo" inputmode="decimal" value="<?= $v('fis_prog_periodo') ?>"></label>
            <label>No período — executado <input name="fis_exec_periodo" inputmode="decimal" value="<?= $v('fis_exec_periodo') ?>"></label>
        </div>
        <div class="linha">
            <label>Até o período — programado <input name="fis_prog_ate" inputmode="decimal" value="<?= $v('fis_prog_ate') ?>"></label>
            <label>Até o período — executado <input name="fis_exec_ate" inputmode="decimal" value="<?= $v('fis_exec_ate') ?>"></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Financeiro — realizado no período (R$) <small>(em branco = 0)</small></legend>
        <div class="linha">
            <label>Concedente <input name="fin_conc_periodo" inputmode="decimal" placeholder="0,00" value="<?= $v('fin_conc_periodo') ?>"></label>
            <label>Executor <input name="fin_exec_periodo" inputmode="decimal" placeholder="0,00" value="<?= $v('fin_exec_periodo') ?>"></label>
            <label>Outros rendimentos <input name="fin_rend_periodo" inputmode="decimal" placeholder="0,00" value="<?= $v('fin_rend_periodo') ?>"></label>
        </div>
        <p class="dica">O total é a soma dos três campos.</p>
    </fieldset>

    <fieldset>
        <legend>Financeiro — realizado até o período (R$)</legend>
        <div class="linha">
            <label>Concedente <input name="fin_conc_ate" inputmode="decimal" placeholder="0,00" value="<?= $v('fin_conc_ate') ?>"></label>
            <label>Executor <input name="fin_exec_ate" inputmode="decimal" placeholder="0,00" value="<?= $v('fin_exec_ate') ?>"></label>
        </div>
        <p class="dica">O total é a soma dos dois campos.</p>
    </fieldset>

    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <a href="<?= e($base) ?>/fisico-financeiro">Cancelar</a>
    </div>
</form>
