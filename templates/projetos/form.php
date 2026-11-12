<h1><?= e($titulo) ?></h1>
<form method="post" action="<?= e($action) ?>" class="form" id="form-projeto">
    <?= csrf_field() ?>
    <label>Município (executor)
        <select name="municipio_id" id="municipio_id" required>
            <option value="">Selecione…</option>
            <?php foreach ($municipios as $m): ?>
                <option value="<?= (int) $m['id'] ?>"
                    data-prefeito="<?= e($m['prefeito_nome']) ?>"
                    data-contador="<?= e($m['contador_nome']) ?>"
                    data-crc="<?= e($m['contador_crc']) ?>"
                    data-local="<?= e($m['nome'] . ' - ' . $m['uf']) ?>"
                    <?= (int) ($projeto['municipio_id'] ?? 0) === (int) $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['nome']) ?> - <?= e($m['uf']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Concedente
        <input name="concedente" maxlength="500" placeholder="450001 - Secretaria de Estado da Educação (SED)" value="<?= e($projeto['concedente'] ?? '') ?>">
    </label>
    <label>Nº do processo
        <input name="processo" maxlength="500" value="<?= e($projeto['processo'] ?? '') ?>">
    </label>
    <div class="linha">
        <label>Nº PP <input name="nr_pp" maxlength="60" value="<?= e($projeto['nr_pp'] ?? '') ?>"></label>
        <label>Nº NE <input name="nr_ne" maxlength="60" value="<?= e($projeto['nr_ne'] ?? '') ?>"></label>
        <label>Nº NL <input name="nr_nl" maxlength="60" value="<?= e($projeto['nr_nl'] ?? '') ?>"></label>
    </div>
    <label>Objeto
        <textarea name="objeto" rows="3" required maxlength="1000"><?= e($projeto['objeto'] ?? '') ?></textarea>
    </label>
    <fieldset>
        <legend>Local, data e assinaturas <small>(em branco = usa os dados do município)</small></legend>
        <div class="linha">
            <label>Local <input name="local" id="local" maxlength="120" value="<?= e($projeto['local'] ?? '') ?>"></label>
            <label>Data de assinatura <input type="date" name="data_assinatura" value="<?= e($projeto['data_assinatura'] ?? '') ?>"></label>
        </div>
        <label>Prefeito <input name="prefeito_nome" id="prefeito_nome" maxlength="120" value="<?= e($projeto['prefeito_nome'] ?? '') ?>"></label>
        <div class="linha">
            <label>Contador <input name="contador_nome" id="contador_nome" maxlength="120" value="<?= e($projeto['contador_nome'] ?? '') ?>"></label>
            <label>CRC do contador <input name="contador_crc" id="contador_crc" maxlength="40" value="<?= e($projeto['contador_crc'] ?? '') ?>"></label>
        </div>
    </fieldset>
    <div class="botoes">
        <button class="btn" type="submit">Salvar</button>
        <a href="<?= $id ? '/projetos/' . (int) $id : '/projetos' ?>">Cancelar</a>
    </div>
</form>
<script src="/assets/projeto-form.js"></script>
