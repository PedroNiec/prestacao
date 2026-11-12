<?php
use App\Domain\Money;

$q = fn (int $v) => Money::formatQuantidade($v);
$m = fn (int $v) => Money::formatOrDash($v);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<?php require __DIR__ . '/_estilo.php'; ?>
<style>
    .secao { border: 0.7pt solid #000; border-top: 0; font-weight: bold; padding: 3pt; }
    table.espaco { margin-top: 8pt; }
</style>
</head>
<body>
<?php require __DIR__ . '/_cabecalho.php'; ?>
<div class="titulo">Relatório de Execução Físico-financeira</div>

<div class="secao">Físico</div>
<table class="grade" style="table-layout:fixed">
    <thead>
        <tr>
            <th rowspan="2" style="width:7%">Meta</th><th rowspan="2" style="width:8%">Etapa / Fase</th>
            <th rowspan="2" style="width:37%">Descrição</th><th rowspan="2" style="width:8%">Unid</th>
            <th colspan="2">No período</th><th colspan="2">Até o período</th>
        </tr>
        <tr><th style="width:10%">Prog</th><th style="width:10%">Exec</th><th style="width:10%">Prog</th><th style="width:10%">Exec</th></tr>
    </thead>
    <tbody>
    <?php foreach ($linhas as $l): ?>
        <tr>
            <td class="c"><?= e($l['meta']) ?></td><td class="c"><?= e($l['etapa']) ?></td>
            <td><?= e($l['descricao']) ?></td><td class="c"><?= e($l['unidade']) ?></td>
            <td class="v"><?= $q((int) $l['fis_prog_periodo']) ?></td><td class="v"><?= $q((int) $l['fis_exec_periodo']) ?></td>
            <td class="v"><?= $q((int) $l['fis_prog_ate']) ?></td><td class="v"><?= $q((int) $l['fis_exec_ate']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$linhas): ?><tr><td colspan="8" class="vazio">Nenhuma meta/etapa.</td></tr><?php endif; ?>
    <?php if ($linhas): ?>
        <tr class="total">
            <td colspan="3">Total</td>
            <?php if ($fisico !== null): ?>
                <td class="c"><?= e($fisico['unidade']) ?></td>
                <td class="v"><?= $q($fisico['fis_prog_periodo']) ?></td><td class="v"><?= $q($fisico['fis_exec_periodo']) ?></td>
                <td class="v"><?= $q($fisico['fis_prog_ate']) ?></td><td class="v"><?= $q($fisico['fis_exec_ate']) ?></td>
            <?php else: ?>
                <td colspan="5" class="c">Unidades diferentes: sem total físico</td>
            <?php endif; ?>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="secao" style="margin-top:8pt; border-top:0.7pt solid #000">Financeiro</div>
<table class="grade" style="table-layout:fixed">
    <thead>
        <tr>
            <th rowspan="2" style="width:7%">Meta</th><th rowspan="2" style="width:8%">Etapa / Fase</th>
            <th colspan="4">Realizado no período</th><th colspan="3">Realizado até o período</th>
        </tr>
        <tr>
            <th style="width:12%">CONCEDENTE</th><th style="width:12%">EXECUTOR</th><th style="width:12%">OUTROS RENDIMENTO</th><th style="width:12%">TOTAL</th>
            <th style="width:12%">CONCEDENTE</th><th style="width:12%">EXECUTOR</th><th style="width:13%">TOTAL</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($linhas as $l): ?>
        <tr>
            <td class="c"><?= e($l['meta']) ?></td><td class="c"><?= e($l['etapa']) ?></td>
            <td class="v"><?= $m((int) $l['fin_conc_periodo']) ?></td><td class="v"><?= $m((int) $l['fin_exec_periodo']) ?></td>
            <td class="v"><?= $m((int) $l['fin_rend_periodo']) ?></td><td class="v"><?= $m($l['total_periodo']) ?></td>
            <td class="v"><?= $m((int) $l['fin_conc_ate']) ?></td><td class="v"><?= $m((int) $l['fin_exec_ate']) ?></td><td class="v"><?= $m($l['total_ate']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$linhas): ?><tr><td colspan="9" class="vazio">Nenhuma meta/etapa.</td></tr><?php endif; ?>
        <tr class="total">
            <td colspan="2">Total</td>
            <td class="v"><?= $m($financeiro['fin_conc_periodo']) ?></td><td class="v"><?= $m($financeiro['fin_exec_periodo']) ?></td>
            <td class="v"><?= $m($financeiro['fin_rend_periodo']) ?></td><td class="v"><?= $m($financeiro['total_periodo']) ?></td>
            <td class="v"><?= $m($financeiro['fin_conc_ate']) ?></td><td class="v"><?= $m($financeiro['fin_exec_ate']) ?></td><td class="v"><?= $m($financeiro['total_ate']) ?></td>
        </tr>
    </tbody>
</table>

<?php require __DIR__ . '/_rodape.php'; ?>
</body>
</html>
