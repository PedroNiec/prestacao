<?php
use App\Core\View;
use App\Domain\Money;

$base = '/projetos/' . (int) $projeto['id'];
$q = fn (int $v) => e(Money::formatQuantidade($v));
$m = fn (int $v) => e(Money::format($v));
?>
<?= View::render("partes/trilha", ["projeto" => $projeto, "ancora" => "#doc-22", "itens" => [["22 Físico-financeiro"]]], false) ?>
<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1>22 — Relatório de execução físico-financeiro</h1>
        <p class="projeto-sub"><?= e($projeto["municipio_nome"]) ?> - <?= e($projeto["municipio_uf"]) ?> · Projeto #<?= (int) $projeto["id"] ?></p>
    </div>
    <a class="btn" href="<?= e($base) ?>/fisico-financeiro/linhas/novo">Nova meta/etapa</a>
</div>
<p class="dica">Todos os valores são digitados por você; nenhum é calculado a partir do balancete. Os totais por linha e do relatório são somas automáticas.</p>

<?= View::render("partes/pdf", [
    "final" => $final, "gerar" => $base . "/fisico-financeiro/gerar", "baixar" => $base . "/documentos/22/baixar",
    "textos" => ["vazio" => "PDF não gerado", "gerado" => "PDF gerado", "desatualizado" => "Os dados mudaram desde a última geração."],
], false) ?>

<?php if (!$linhas): ?>
    <div class="estado-vazio">
        <strong>Nenhuma meta/etapa ainda.</strong>
        Adicione ao menos uma linha.<br>
        <a class="btn" href="<?= e($base) ?>/fisico-financeiro/linhas/novo">Nova meta/etapa</a>
    </div>
<?php else: ?>
<section class="cartao secao">
<h2>Físico</h2>
<div class="rolagem"><table>
    <thead>
        <tr><th rowspan="2">Meta</th><th rowspan="2">Etapa</th><th rowspan="2">Descrição</th><th rowspan="2">Unid</th><th colspan="2" class="num">No período</th><th colspan="2" class="num">Até o período</th><th rowspan="2"></th></tr>
        <tr><th class="num">Prog</th><th class="num">Exec</th><th class="num">Prog</th><th class="num">Exec</th></tr>
    </thead>
    <tbody>
    <?php foreach ($linhas as $i => $l): $lid = (int) $l['id']; ?>
        <tr id="l<?= $lid ?>">
            <td><?= e($l['meta']) ?></td><td><?= e($l['etapa']) ?></td><td><?= e($l['descricao']) ?></td><td><?= e($l['unidade']) ?></td>
            <td class="num"><?= $q((int) $l['fis_prog_periodo']) ?></td><td class="num"><?= $q((int) $l['fis_exec_periodo']) ?></td>
            <td class="num"><?= $q((int) $l['fis_prog_ate']) ?></td><td class="num"><?= $q((int) $l['fis_exec_ate']) ?></td>
            <td class="acoes">
                <a href="<?= e($base) ?>/fisico-financeiro/linhas/<?= $lid ?>/editar">Editar</a>
                <form method="post" action="<?= e($base) ?>/fisico-financeiro/linhas/<?= $lid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="cima"><button class="link" type="submit" aria-label="Mover para cima" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
                <form method="post" action="<?= e($base) ?>/fisico-financeiro/linhas/<?= $lid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="baixo"><button class="link" type="submit" aria-label="Mover para baixo" <?= $i === count($linhas) - 1 ? 'disabled' : '' ?>>↓</button></form>
                <form method="post" action="<?= e($base) ?>/fisico-financeiro/linhas/<?= $lid ?>/excluir" onsubmit="return confirm('Excluir esta meta/etapa?')"><?= csrf_field() ?><button class="link perigo" type="submit">Excluir</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <?php if ($fisico !== null): ?>
            <th colspan="3">Total</th><th><?= e($fisico['unidade']) ?></th>
            <th class="num"><?= $q($fisico['fis_prog_periodo']) ?></th><th class="num"><?= $q($fisico['fis_exec_periodo']) ?></th>
            <th class="num"><?= $q($fisico['fis_prog_ate']) ?></th><th class="num"><?= $q($fisico['fis_exec_ate']) ?></th><th></th>
        <?php else: ?>
            <th colspan="9">Total físico indisponível: as metas usam unidades diferentes (somar unidades distintas não faz sentido).</th>
        <?php endif; ?>
    </tr></tfoot>
</table></div>
</section>

<section class="cartao secao">
<h2>Financeiro</h2>
<div class="rolagem"><table>
    <thead>
        <tr><th rowspan="2">Meta</th><th rowspan="2">Etapa</th><th colspan="4" class="num">Realizado no período</th><th colspan="3" class="num">Realizado até o período</th></tr>
        <tr><th class="num">Concedente</th><th class="num">Executor</th><th class="num">Outros rend.</th><th class="num">Total</th><th class="num">Concedente</th><th class="num">Executor</th><th class="num">Total</th></tr>
    </thead>
    <tbody>
    <?php foreach ($linhas as $l): ?>
        <tr>
            <td><?= e($l['meta']) ?></td><td><?= e($l['etapa']) ?></td>
            <td class="num"><?= $m((int) $l['fin_conc_periodo']) ?></td><td class="num"><?= $m((int) $l['fin_exec_periodo']) ?></td>
            <td class="num"><?= $m((int) $l['fin_rend_periodo']) ?></td><td class="num"><strong><?= $m($l['total_periodo']) ?></strong></td>
            <td class="num"><?= $m((int) $l['fin_conc_ate']) ?></td><td class="num"><?= $m((int) $l['fin_exec_ate']) ?></td><td class="num"><strong><?= $m($l['total_ate']) ?></strong></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <th colspan="2">Total</th>
        <th class="num"><?= $m($financeiro['fin_conc_periodo']) ?></th><th class="num"><?= $m($financeiro['fin_exec_periodo']) ?></th>
        <th class="num"><?= $m($financeiro['fin_rend_periodo']) ?></th><th class="num"><?= $m($financeiro['total_periodo']) ?></th>
        <th class="num"><?= $m($financeiro['fin_conc_ate']) ?></th><th class="num"><?= $m($financeiro['fin_exec_ate']) ?></th><th class="num"><?= $m($financeiro['total_ate']) ?></th>
    </tr></tfoot>
</table></div>
</section>
<?php endif; ?>
