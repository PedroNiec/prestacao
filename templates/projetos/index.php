<?php $total = count($projetos); ?>
<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1>Projetos</h1>
        <p class="projeto-sub"><?= $total === 1 ? '1 projeto' : $total . ' projetos' ?></p>
    </div>
    <a class="btn" href="/projetos/novo">+ Novo projeto</a>
</div>
<?php if (!$projetos): ?>
    <div class="estado-vazio estado-vazio--pasta">
        <strong>Nenhum projeto cadastrado ainda.</strong>
        <?php if (!$temMunicipios): ?>
            Comece cadastrando um município.<br>
            <a class="btn" href="/municipios/novo">Cadastrar município</a>
        <?php else: ?>
            Cada projeto reúne os 25 documentos de uma prestação de contas.<br>
            <a class="btn" href="/projetos/novo">Novo projeto</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <ul class="lista-projetos">
    <?php foreach ($projetos as $p):
        $pid = (int) $p['id'];
        $sub = e($p['municipio_nome']) . ' - ' . e($p['municipio_uf'])
            . (trim((string) $p['processo']) !== '' ? ' · ' . e($p['processo']) : '')
            . ' · #' . $pid;
    ?>
        <li class="cartao-projeto">
            <span class="icone-pasta" aria-hidden="true"></span>
            <div>
                <h2 class="cartao-projeto-titulo"><a class="projeto-link" href="/projetos/<?= $pid ?>"><?= trim($p['objeto']) !== '' ? e($p['objeto']) : 'Projeto #' . $pid ?></a></h2>
                <span class="item-sub"><?= $sub ?></span>
            </div>
            <span class="cartao-projeto-abrir" aria-hidden="true">Abrir ›</span>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
