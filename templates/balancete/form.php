<?php
$base = '/projetos/' . (int) $projeto['id'];
$tipo = $l['tipo'] ?? 'despesa';
$origem = $l['origem'] ?? 'convenio';
$fonte = (string) ($l['fonte'] ?? ($origem === 'contrapartida' ? 2 : 1));
?>
<p><a href="<?= e($base) ?>/balancete">← Voltar ao balancete</a></p>
<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form" id="form-lancamento">
    <?= csrf_field() ?>
    <fieldset>
        <legend>Tipo</legend>
        <label class="inline"><input type="radio" name="tipo" value="receita" <?= $tipo === 'receita' ? 'checked' : '' ?> required> Receita (entrou na conta)</label>
        <label class="inline"><input type="radio" name="tipo" value="despesa" <?= $tipo === 'despesa' ? 'checked' : '' ?>> Despesa (saiu da conta)</label>
    </fieldset>
    <label>Descrição
        <input name="descricao" required maxlength="300" value="<?= e($l['descricao'] ?? '') ?>" autofocus>
    </label>
    <div class="linha">
        <label>Valor (R$)
            <input name="valor" required inputmode="decimal" placeholder="1.234,56" value="<?= e($l['valor'] ?? '') ?>">
        </label>
        <label>Data
            <input type="date" name="data" value="<?= e($l['data'] ?? '') ?>">
        </label>
    </div>
    <div class="linha">
        <label>Origem do recurso
            <select name="origem" id="origem" required>
                <?php foreach ($origens as $k => $nome): ?>
                    <option value="<?= e($k) ?>" <?= $origem === $k ? 'selected' : '' ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Fonte
            <select name="fonte" id="fonte">
                <option value="1" <?= $fonte === '1' ? 'selected' : '' ?>>1 - Concedente</option>
                <option value="2" <?= $fonte === '2' ? 'selected' : '' ?>>2 - Convenente</option>
            </select>
        </label>
    </div>
    <div class="linha">
        <label>Boletim de medição / parcela <input name="boletim_parcela" maxlength="60" value="<?= e($l['boletim_parcela'] ?? '') ?>"></label>
        <label>CNPJ <input name="cnpj" maxlength="30" placeholder="00.000.000/0000-00" value="<?= e($l['cnpj'] ?? '') ?>"></label>
    </div>
    <div class="linha">
        <label>Nota fiscal <input name="nota_fiscal" maxlength="40" value="<?= e($l['nota_fiscal'] ?? '') ?>"></label>
        <label>Nota de empenho <input name="nota_empenho" maxlength="40" value="<?= e($l['nota_empenho'] ?? '') ?>"></label>
        <label>DOC <input name="doc" maxlength="60" value="<?= e($l['doc'] ?? '') ?>"></label>
    </div>
    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <?php if (!$edicao): ?><button class="btn secundario" type="submit" name="outro" value="1">Salvar e adicionar outro</button><?php endif; ?>
        <a href="<?= e($base) ?>/balancete">Cancelar</a>
    </div>
</form>
<script src="/assets/lancamento-form.js"></script>
