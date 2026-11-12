<div class="barra">
    <h1>Municípios</h1>
    <a class="btn" href="/municipios/novo">Novo município</a>
</div>
<?php if (!$municipios): ?>
    <p class="aviso">Nenhum município cadastrado ainda.</p>
<?php else: ?>
<div class="rolagem"><table>
    <thead><tr><th>Município</th><th>Prefeito</th><th>Contador</th><th>Projetos</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($municipios as $m): ?>
        <tr>
            <td><?= e($m['nome']) ?> - <?= e($m['uf']) ?></td>
            <td><?= e($m['prefeito_nome']) ?></td>
            <td><?= e($m['contador_nome']) ?><?= $m['contador_crc'] !== '' ? ' — CRC ' . e($m['contador_crc']) : '' ?></td>
            <td><?= (int) $m['total_projetos'] ?></td>
            <td class="acoes">
                <a href="/municipios/<?= (int) $m['id'] ?>/editar">Editar</a>
                <form method="post" action="/municipios/<?= (int) $m['id'] ?>/excluir" onsubmit="return confirm('Excluir este município?')">
                    <?= csrf_field() ?>
                    <button class="link perigo" type="submit">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
