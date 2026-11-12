<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <label>Nome do município
        <input name="nome" required maxlength="120" value="<?= e($municipio['nome'] ?? '') ?>">
    </label>
    <label>UF
        <input name="uf" required maxlength="2" size="2" style="text-transform:uppercase" value="<?= e($municipio['uf'] ?? 'SC') ?>">
    </label>
    <fieldset>
        <legend>Signatários padrão</legend>
        <label>Prefeito
            <input name="prefeito_nome" maxlength="120" value="<?= e($municipio['prefeito_nome'] ?? '') ?>">
        </label>
        <label>Contador
            <input name="contador_nome" maxlength="120" value="<?= e($municipio['contador_nome'] ?? '') ?>">
        </label>
        <label>CRC do contador
            <input name="contador_crc" maxlength="40" placeholder="CRC/SC 000.000" value="<?= e($municipio['contador_crc'] ?? '') ?>">
        </label>
    </fieldset>
    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <a href="/municipios">Cancelar</a>
    </div>
</form>
