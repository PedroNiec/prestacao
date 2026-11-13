<?php
use App\Presenters\Iniciais;

$total = count($municipios);
$valor = fn ($v): string => trim((string) $v) !== '' ? e((string) $v) : '—';
?>
<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1>Municípios</h1>
        <p class="projeto-sub"><?= $total === 1 ? '1 município cadastrado' : $total . ' municípios cadastrados' ?></p>
    </div>
    <a class="btn" href="/municipios/novo">+ Novo município</a>
</div>
<?php if (!$municipios): ?>
    <div class="estado-vazio estado-vazio--icone">
        <strong>Nenhum município cadastrado ainda.</strong>
        Os dados do prefeito e do contador cadastrados aqui são copiados para cada projeto novo.<br>
        <a class="btn" href="/municipios/novo">Novo município</a>
    </div>
<?php else: ?>
    <ul class="grade-cartoes">
    <?php foreach ($municipios as $m):
        $mid = (int) $m['id'];
        $n = (int) $m['total_projetos'];
        $nome = $m['nome'] . ' - ' . $m['uf'];
    ?>
        <li class="cartao-mun">
            <div class="cartao-mun-cab">
                <span class="avatar" aria-hidden="true"><?= e(Iniciais::de((string) $m['nome'])) ?></span>
                <div class="cartao-mun-id">
                    <h2 class="cartao-mun-nome"><?= e($m['nome']) ?></h2>
                    <span class="item-sub"><?= e($m['uf']) ?> · <?= $n ?> projeto(s)</span>
                </div>
            </div>
            <dl class="cartao-mun-dados">
                <div><dt>Prefeito</dt><dd><?= $valor($m['prefeito_nome']) ?></dd></div>
                <div><dt>Contador</dt><dd><?= $valor($m['contador_nome']) ?><?php if ($m['contador_crc'] !== ''): ?> <small><?= e($m['contador_crc']) ?></small><?php endif; ?></dd></div>
            </dl>
            <div class="cartao-mun-rodape">
                <a class="btn secundario pequeno" href="/municipios/<?= $mid ?>/editar">Editar</a>
                <details class="menu-mais">
                    <summary aria-label="Mais ações para <?= e($nome) ?>">⋮</summary>
                    <div class="menu-mais-painel">
                    <?php if ($n > 0): ?>
                        <button class="link perigo" type="button" disabled>Excluir</button>
                        <small class="menu-mais-motivo">Possui <?= $n ?> projeto(s). Exclua os projetos antes.</small>
                    <?php else: ?>
                        <form method="post" action="/municipios/<?= $mid ?>/excluir" onsubmit="return confirm('Excluir este município?')">
                            <?= csrf_field() ?>
                            <button class="link perigo" type="submit">Excluir</button>
                        </form>
                    <?php endif; ?>
                    </div>
                </details>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
