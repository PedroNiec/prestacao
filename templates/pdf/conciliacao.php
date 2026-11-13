<?php
use App\Domain\Dates;
use App\Domain\Money;

$margem = '34pt 30pt 34pt 40pt';
$fonte = '9pt';
$rodapeSoPrefeito = true;
$rodapeSeparador = ', em ';
$m = fn (int $v) => Money::format($v);
$dataSaldo = Dates::br((string) $config['data_saldo']);
$emData = $dataSaldo !== '' ? " em $dataSaldo" : '';
$rotuloExtrato = $config['rotulo_extrato'] !== '' ? $config['rotulo_extrato'] : 'VALOR - R$';
$tituloLinha2 = $config['titulo'] !== '' ? $config['titulo'] : '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<?php require __DIR__ . '/_estilo.php'; ?>
<style>
    table.conc td, table.conc th { border: 0.7pt solid #000; padding: 3pt 4pt; }
    .cinza { background: #e7e6e6; }
    .tit { text-align: center; font-weight: bold; font-size: 10.5pt; padding: 5pt 4pt; }
    .esp td { border: 0; padding: 3pt 0; }
    td.n { text-align: right; white-space: nowrap; }
    .neg { font-weight: bold; }
    /* linhas auxiliares que só fixam a largura das colunas: sem altura */
    tr.larg th { padding: 0; border: 0; height: 0; line-height: 0; font-size: 0; }
    .assinaturas { margin-top: 18pt; }
    .local { margin-bottom: 26pt; }
</style>
</head>
<body>
<?php require __DIR__ . '/_cabecalho.php'; ?>
<table class="conc" style="table-layout:fixed; margin-top:0">
    <thead>
        <tr class="larg"><th style="width:7%"></th><th style="width:47%"></th><th style="width:23%"></th><th style="width:23%"></th></tr>
        <tr><th class="cinza tit" colspan="4" style="border-top:0">BALANCETE DE VERIFICAÇÃO - CONCILIAÇÃO BANCÁRIA</th></tr>
        <?php if ($tituloLinha2 !== ''): ?><tr><th class="cinza tit" colspan="4" style="border-top:0"><?= e($tituloLinha2) ?></th></tr><?php endif; ?>
    </thead>
    <tbody>
        <tr class="esp"><td colspan="4"></td></tr>

        <tr class="neg"><td>1.0</td><td style="text-align:center">RECEITAS</td><td style="text-align:center">VALOR - R$</td><td style="text-align:center">SOMA - R$</td></tr>
        <?php $soma = 0; foreach ($receitas as $i => $r): $soma += $r['valor']; ?>
        <tr><td>1.<?= $i + 1 ?></td><td><?= e($r['rotulo']) ?></td><td class="n"><?= $m($r['valor']) ?></td><td class="n"><?= $m($soma) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$receitas): ?><tr><td></td><td colspan="3" class="vazio">Nenhuma receita informada.</td></tr><?php endif; ?>
        <tr class="cinza neg"><td>1.0</td><td>TOTAL RECEITAS<?= $receitas ? ' ( 1.1 até 1.' . count($receitas) . ' )' : '' ?></td><td class="n"><?= $m($totais['receitas']) ?></td><td class="n"><?= $m($totais['receitas']) ?></td></tr>
        <tr class="esp"><td colspan="4"></td></tr>

        <tr class="neg"><td>2.0</td><td style="text-align:center">DESPESAS</td><td style="text-align:center">VALOR - R$</td><td style="text-align:center">SOMA - R$</td></tr>
        <?php foreach ($notas as $n): foreach ($n['bloco'] as $b): ?>
        <tr class="<?= $b['destaque'] ? 'neg' : '' ?>"><td></td><td><?= e($b['rotulo']) ?></td><td class="n"><?= $m($b['valor']) ?></td><td class="n"><?= $m($b['soma']) ?></td></tr>
        <?php endforeach; endforeach; ?>
        <?php if (!$notas): ?><tr><td></td><td colspan="3" class="vazio">Nenhuma nota fiscal informada.</td></tr><?php endif; ?>
        <?php $soma = 0; foreach (\App\Domain\ConciliacaoCalculator::DEVOLUCOES as $campo => $rotulo): $soma += (int) $config[$campo]; ?>
        <tr><td></td><td><?= e($rotulo) ?></td><td class="n"><?= $m((int) $config[$campo]) ?></td><td class="n"><?= $m($soma) ?></td></tr>
        <?php endforeach; ?>
        <tr class="cinza neg"><td>2.0</td><td>TOTAL DESPESAS (2)</td><td class="n"><?= $m($totais['despesas']) ?></td><td class="n"><?= $m($totais['despesas']) ?></td></tr>
        <tr class="esp"><td colspan="4"></td></tr>

        <tr class="cinza neg"><td>3.0</td><td>SALDO (RECEITAS - DESPESAS)</td><td class="n"><?= $m($totais['saldo']) ?></td><td class="n"><?= $m($totais['saldo']) ?></td></tr>
        <tr class="esp"><td colspan="4"></td></tr>

    </tbody>
</table>

<?php /* Extrato, conciliação e assinatura formam um bloco único: nunca são separados entre páginas. */ ?>
<div style="page-break-inside: avoid">
<table class="conc" style="table-layout:fixed; margin-top:6pt; page-break-inside: avoid">
    <thead>
        <tr class="larg"><th style="width:7%"></th><th style="width:47%"></th><th style="width:23%"></th><th style="width:23%"></th></tr>
    </thead>
    <tbody>
        <tr><td colspan="4" class="cinza tit">EXTRATO BANCÁRIO<?= $config['extrato_descricao'] !== '' ? ' - ' . e($config['extrato_descricao']) : ' -' ?></td></tr>
        <tr class="neg"><td>4.0</td><td style="text-align:center">Conta Corrente</td><td style="text-align:center"><?= e($rotuloExtrato) ?></td><td style="text-align:center">SOMA - R$</td></tr>
        <tr><td>4.1</td><td>Saldo Conta Corrente<?= e($emData) ?></td><td class="n"><?= $m((int) $config['saldo_cc']) ?></td><td class="n"><?= $m((int) $config['saldo_cc']) ?></td></tr>
        <tr><td>4.2</td><td>Saldo Conta Aplicações/Investimentos<?= e($emData) ?></td><td class="n"><?= $m((int) $config['saldo_aplic']) ?></td><td class="n"><?= $m((int) $config['saldo_aplic']) ?></td></tr>
        <tr class="cinza neg"><td>4.0</td><td>Saldo em Conta Bancária (4.1 + 4.2)</td><td class="n"><?= $m($totais['bancario']) ?></td><td class="n"><?= $m($totais['bancario']) ?></td></tr>
        <tr class="esp"><td colspan="4"></td></tr>
        <tr class="cinza neg"><td>5.0</td><td>Conciliação Bancária (3.0 - 4.0)</td><td class="n"><?= $m($totais['conciliacao']) ?></td><td class="n"><?= $m($totais['conciliacao']) ?></td></tr>
    </tbody>
</table>

<?php require __DIR__ . '/_rodape.php'; ?>
</div>
</body>
</html>
