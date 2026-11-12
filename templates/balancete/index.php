<?php
use App\Domain\BalanceteCalculator;
use App\Domain\Dates;
use App\Domain\Money;

$base = '/projetos/' . (int) $projeto['id'];
$desatualizado = $final && (int) $final['desatualizado'] === 1;
$o = $totais['origens'];
?>
<p><a href="<?= e($base) ?>#doc-01">← Voltar ao projeto #<?= (int) $projeto['id'] ?></a></p>
<div class="barra">
    <h1>01 — Balancete</h1>
    <a class="btn" href="<?= e($base) ?>/balancete/lancamentos/novo">Novo lançamento</a>
</div>
<p>Receita é o que <strong>entrou</strong> na conta; despesa é o que <strong>saiu</strong>. Retenções (IRRF, ISS, INSS) são lançadas como linhas de despesa.</p>

<div class="caixa">
    <?php if (!$final): ?>
        <span class="tag vazio">PDF não gerado</span>
    <?php elseif ($desatualizado): ?>
        <span class="tag aviso-tag">Desatualizado</span> Os lançamentos mudaram desde a última geração.
    <?php else: ?>
        <span class="tag ok">PDF gerado</span> <small><?= e(format_bytes((int) $final['tamanho'])) ?> · <?= e($final['atualizado_em']) ?></small>
    <?php endif; ?>
    <div class="acoes" style="margin-top:.6rem">
        <form method="post" action="<?= e($base) ?>/balancete/gerar"><?= csrf_field() ?><button class="btn" type="submit">Gerar PDF</button></form>
        <?php if ($final): ?>
            <a href="<?= e($base) ?>/documentos/01/baixar?ver=1" target="_blank" rel="noopener">Ver</a>
            <a href="<?= e($base) ?>/documentos/01/baixar">Baixar</a>
        <?php endif; ?>
    </div>
</div>

<h2>Lançamentos (<?= count($lancamentos) ?>)</h2>
<?php if (!$lancamentos): ?>
    <p class="aviso">Nenhum lançamento ainda. Comece lançando a transferência recebida (receita).</p>
<?php else: ?>
<div class="rolagem">
<table>
    <thead><tr>
        <th>Parc.</th><th>Descrição</th><th>CNPJ</th><th>NF</th><th>Empenho</th><th>DOC</th><th>Data</th><th>Origem</th><th>Fonte</th>
        <th class="num">Receita</th><th class="num">Despesa</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($lancamentos as $i => $l): $lid = (int) $l['id']; ?>
        <tr id="l<?= $lid ?>">
            <td><?= e($l['boletim_parcela']) ?></td>
            <td><?= e($l['descricao']) ?></td>
            <td><?= e($l['cnpj']) ?></td>
            <td><?= e($l['nota_fiscal']) ?></td>
            <td><?= e($l['nota_empenho']) ?></td>
            <td><?= e($l['doc']) ?></td>
            <td><?= e(Dates::br((string) $l['data'])) ?></td>
            <td><?= e(BalanceteCalculator::ORIGENS[$l['origem']] ?? $l['origem']) ?></td>
            <td><?= (int) $l['fonte'] ?></td>
            <td class="num"><?= $l['tipo'] === 'receita' ? e(Money::format((int) $l['valor_centavos'])) : '' ?></td>
            <td class="num"><?= $l['tipo'] === 'despesa' ? e(Money::format((int) $l['valor_centavos'])) : '' ?></td>
            <td class="acoes">
                <a href="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/editar">Editar</a>
                <form method="post" action="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="cima"><button class="link" type="submit" aria-label="Mover para cima" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
                <form method="post" action="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="baixo"><button class="link" type="submit" aria-label="Mover para baixo" <?= $i === count($lancamentos) - 1 ? 'disabled' : '' ?>>↓</button></form>
                <form method="post" action="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/excluir" onsubmit="return confirm('Excluir este lançamento?')"><?= csrf_field() ?><button class="link perigo" type="submit">Excluir</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="9">Total geral</th><th class="num"><?= e(Money::format($totais['receita'])) ?></th><th class="num"><?= e(Money::format($totais['despesa'])) ?></th><th></th></tr>
        <tr><th colspan="9">Saldo (receita − despesa)</th><th class="num" colspan="2"><?= e(Money::format($totais['saldo'])) ?></th><th></th></tr>
    </tfoot>
</table>
</div>

<h2>Totais por origem</h2>
<table class="compacta">
    <thead><tr><th>Origem</th><th class="num">Receita</th><th class="num">Despesa</th></tr></thead>
    <tbody>
    <?php foreach (BalanceteCalculator::ORIGENS as $k => $nome): ?>
        <tr><td><?= e($nome) ?></td><td class="num"><?= e(Money::format($o[$k]['receita'])) ?></td><td class="num"><?= e(Money::format($o[$k]['despesa'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
