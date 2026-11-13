<?php
/**
 * Relação de bens (doc. 09) — aba "Rel de Bens construidos" da planilha (specs/relacao-de-bens.md, RF-B06).
 * Espera $projeto, $bens (lista com descricao e valor_centavos), $total (centavos) e $tituloDocumento.
 */
use App\Domain\Money;

$margem = '28pt 28pt 28pt 28pt';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<?php require __DIR__ . '/_estilo.php'; ?>
<style>
    .bens { margin-top: 10pt; }
    .bens td.item { width: 8%; text-align: center; }
    .bens td.desc { width: 70%; }
    .bens td.valor { width: 22%; text-align: right; white-space: nowrap; }
</style>
</head>
<body>
<?php require __DIR__ . '/_cabecalho.php'; ?>
<div class="titulo"><?= e($tituloDocumento) ?></div>

<table class="grade bens">
<?php if (!$bens): ?>
    <tr><td class="vazio" colspan="3">Nenhum bem cadastrado.</td></tr>
<?php endif; ?>
<?php foreach ($bens as $i => $b): ?>
    <tr>
        <td class="item"><?= $i + 1 ?></td>
        <td class="desc"><?= e($b['descricao']) ?></td>
        <td class="valor"><?= e(Money::format((int) $b['valor_centavos'])) ?></td>
    </tr>
<?php endforeach; ?>
    <tr class="total">
        <td class="item"></td>
        <td class="desc">TOTAL</td>
        <td class="valor"><?= e(Money::format((int) $total)) ?></td>
    </tr>
</table>

<?php require __DIR__ . '/_rodape.php'; ?>
</body>
</html>
