<?php
use App\Domain\BalanceteCalculator;
use App\Domain\Dates;
use App\Domain\Money;

$o = $totais['origens'];
$cel = fn (int $v) => Money::formatOrDash($v);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<?php require __DIR__ . '/_estilo.php'; ?>
</head>
<body>
<?php require __DIR__ . '/_cabecalho.php'; ?>
<div class="titulo">BALANCETE DE PRESTAÇÃO DE CONTAS - RECEITA / DESPESA / SALDO</div>

<table class="grade" style="table-layout:fixed">
    <thead>
        <tr>
            <th style="width:7%">Boletim de Medição / Parcela</th><th style="width:26%">DESCRIÇÃO</th><th style="width:11%">CNPJ</th>
            <th style="width:6%">Nota Fiscal</th><th style="width:8%">Nota de Empenho</th><th style="width:11%">DOC</th>
            <th style="width:7%">Data</th><th style="width:4%">Fonte</th><th style="width:10%">Receita</th><th style="width:10%">Despesa</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($lancamentos as $l): ?>
        <tr>
            <td class="c"><?= e($l['boletim_parcela']) ?></td>
            <td><?= e($l['descricao']) ?></td>
            <td class="c"><?= e($l['cnpj']) ?></td>
            <td class="c"><?= e($l['nota_fiscal']) ?></td>
            <td class="c"><?= e($l['nota_empenho']) ?></td>
            <td class="c"><?= e($l['doc']) ?></td>
            <td class="c"><?= e(Dates::br((string) $l['data'])) ?></td>
            <td class="c"><?= (int) $l['fonte'] ?></td>
            <td class="v"><?= $l['tipo'] === 'receita' ? Money::format((int) $l['valor_centavos']) : '' ?></td>
            <td class="v"><?= $l['tipo'] === 'despesa' ? Money::format((int) $l['valor_centavos']) : '' ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lancamentos): ?>
        <tr><td colspan="10" class="vazio">Nenhum lançamento.</td></tr>
    <?php endif; ?>
        <tr class="total"><td colspan="8">TOTAL GERAL</td><td class="v"><?= $cel($totais['receita']) ?></td><td class="v"><?= $cel($totais['despesa']) ?></td></tr>
        <tr class="total"><td colspan="8">SALDO (RECEITA - DESPESA)</td><td class="v" colspan="2"><?= Money::format($totais['saldo']) ?></td></tr>
        <tr class="total"><td colspan="8">TOTAL RECURSO DO CONVÊNIO</td><td class="v"><?= $cel($o['convenio']['receita']) ?></td><td class="v"><?= $cel($o['convenio']['despesa']) ?></td></tr>
        <tr class="total"><td colspan="8">TOTAL RECURSO DE RENDIMENTOS DA APLICAÇÃO FINANCEIRA</td><td class="v"><?= $cel($o['rendimentos']['receita']) ?></td><td class="v"><?= $cel($o['rendimentos']['despesa']) ?></td></tr>
        <tr class="total"><td colspan="8">TOTAL RECURSO DE CONTRAPARTIDA</td><td class="v"><?= $cel($o['contrapartida']['receita']) ?></td><td class="v"><?= $cel($o['contrapartida']['despesa']) ?></td></tr>
    </tbody>
</table>

<table class="semborda" style="margin-top:10pt; page-break-inside: avoid">
    <tr>
        <td style="width:55%; vertical-align:top">
            <strong>resumo:</strong>
            <table class="semborda total">
                <tr><td style="width:75%">TOTAL DE TRANSFERÊNCIA ESPECIAL RECEBIDA</td><td class="v"><?= $cel($o['convenio']['receita']) ?></td></tr>
                <tr><td>TOTAL DE RENDIMENTOS AUFERIDOS</td><td class="v"><?= $cel($o['rendimentos']['receita']) ?></td></tr>
                <tr><td>TOTAL DE CONTRAPARTIDA MUNICIPAL</td><td class="v"><?= $cel($o['contrapartida']['receita']) ?></td></tr>
            </table>
        </td>
        <td style="vertical-align:top; padding-left:40pt">
            <table class="grade total">
                <tr><td class="c">FONTES:</td></tr>
                <tr><td class="c">1 - Concedente</td></tr>
                <tr><td class="c">2 - Convenente</td></tr>
            </table>
        </td>
    </tr>
</table>

<?php require __DIR__ . '/_rodape.php'; ?>
</body>
</html>
