<div class="barra">
    <h1>Projetos</h1>
    <a class="btn" href="/projetos/novo">Novo projeto</a>
</div>
<?php if (!$projetos): ?>
    <p class="aviso">
        Nenhum projeto cadastrado ainda.
        <?php if (!$temMunicipios): ?>Comece <a href="/municipios/novo">cadastrando um município</a>.<?php endif; ?>
    </p>
<?php else: ?>
<div class="rolagem"><table>
    <thead><tr><th>#</th><th>Município</th><th>Objeto</th><th>Processo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($projetos as $p): ?>
        <tr>
            <td><?= (int) $p['id'] ?></td>
            <td><?= e($p['municipio_nome']) ?> - <?= e($p['municipio_uf']) ?></td>
            <td><?= e($p['objeto']) ?></td>
            <td><?= e($p['processo']) ?></td>
            <td class="acoes"><a href="/projetos/<?= (int) $p['id'] ?>">Abrir</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
