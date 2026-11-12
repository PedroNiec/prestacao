<?php
use App\Domain\ConciliacaoCalculator;
use App\Domain\Money;

$base = '/projetos/' . (int) $projeto['id'];
$desatualizado = $final && (int) $final['desatualizado'] === 1;
$m = fn (int $v) => e(Money::format($v));
$diferenca = $totais['conciliacao'] !== 0;
$v = fn (string $k) => e($config[$k] ?? '');
$vm = fn (string $k) => isset($config[$k]) && is_numeric($config[$k]) ? e(Money::input((int) $config[$k])) : e($config[$k] ?? '');
?>
<p><a href="<?= e($base) ?>#doc-15">← Voltar ao projeto #<?= (int) $projeto['id'] ?></a></p>
<div class="barra"><h1>15 — Conciliação bancária</h1></div>

<div class="caixa">
    <?php if (!$final): ?>
        <span class="tag vazio">PDF não gerado</span>
    <?php elseif ($desatualizado): ?>
        <span class="tag aviso-tag">Desatualizado</span> Os dados (ou o balancete) mudaram desde a última geração.
    <?php else: ?>
        <span class="tag ok">PDF gerado</span> <small><?= e(format_bytes((int) $final['tamanho'])) ?> · <?= e($final['atualizado_em']) ?></small>
    <?php endif; ?>
    <div class="acoes" style="margin-top:.6rem">
        <form method="post" action="<?= e($base) ?>/conciliacao/gerar"><?= csrf_field() ?><button class="btn" type="submit">Gerar PDF</button></form>
        <?php if ($final): ?>
            <a href="<?= e($base) ?>/documentos/15/baixar?ver=1" target="_blank" rel="noopener">Ver</a>
            <a href="<?= e($base) ?>/documentos/15/baixar">Baixar</a>
        <?php endif; ?>
    </div>
</div>

<h2>Resumo</h2>
<table class="compacta">
    <tbody>
        <tr><td>Total de receitas</td><td class="num"><?= $m($totais['receitas']) ?></td></tr>
        <tr><td>Total de despesas (soma do bruto das notas)</td><td class="num"><?= $m($totais['despesas']) ?></td></tr>
        <tr><td><strong>Saldo (receitas − despesas)</strong></td><td class="num"><strong><?= $m($totais['saldo']) ?></strong></td></tr>
        <tr><td>Saldo em conta bancária (corrente + aplicação)</td><td class="num"><?= $m($totais['bancario']) ?></td></tr>
        <tr><td><strong>Conciliação (saldo − bancário)</strong></td><td class="num"><strong><?= $m($totais['conciliacao']) ?></strong></td></tr>
    </tbody>
</table>
<?php if ($diferenca): ?>
    <p class="aviso">A conciliação <strong>não fechou</strong>: diferença de R$ <?= $m($totais['conciliacao']) ?>.
    <?php if ($totais['devolucoes'] > 0): ?>
        As devoluções (R$ <?= $m($totais['devolucoes']) ?>) <strong>não entram</strong> no total de despesas, o que pode explicar a diferença.
    <?php endif; ?>
    </p>
<?php endif; ?>

<h2 id="receitas">1. Receitas <small>(calculadas a partir do balancete, editáveis)</small></h2>
<form method="post" action="<?= e($base) ?>/conciliacao/receitas" class="form-larga">
    <?= csrf_field() ?>
    <div class="rolagem"><table>
        <thead><tr><th>#</th><th>Descrição</th><th class="num">Do balancete</th><th>Valor na conciliação (R$)</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($receitas as $i => $r): ?>
            <tr>
                <td>1.<?= $i + 1 ?></td>
                <td><?= e($r['rotulo']) ?></td>
                <td class="num"><?= $m($r['calculado']) ?></td>
                <td>
                    <input name="valores[<?= e($r['chave']) ?>]" inputmode="decimal" value="<?= e(Money::input($r['valor'])) ?>" aria-label="Valor de <?= e($r['rotulo']) ?>">
                </td>
                <td>
                    <?php if ($r['alterado']): ?>
                        <span class="tag aviso-tag" title="Diferente do balancete">alterado</span>
                        <button class="link" type="submit" form="restaurar-<?= $i ?>">Restaurar</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="3">Total de receitas</th><th class="num"><?= $m($totais['receitas']) ?></th><th></th></tr></tfoot>
    </table></div>
    <div class="botoes" style="margin-top:.6rem"><button class="btn" type="submit">Salvar receitas</button></div>
</form>
<?php foreach ($receitas as $i => $r): if ($r['alterado']): ?>
    <form method="post" id="restaurar-<?= $i ?>" action="<?= e($base) ?>/conciliacao/receitas/restaurar"><?= csrf_field() ?><input type="hidden" name="chave" value="<?= e($r['chave']) ?>"></form>
<?php endif; endforeach; ?>

<div class="barra" id="notas" style="margin-top:2rem">
    <h2 style="margin:0">2. Notas fiscais</h2>
    <a class="btn" href="<?= e($base) ?>/conciliacao/notas/novo">Nova nota fiscal</a>
</div>
<?php if (!$notas): ?>
    <p class="aviso">Nenhuma nota fiscal. Cada nota gera um bloco com bruto, líquido e retenções.</p>
<?php else: ?>
<div class="rolagem"><table>
    <thead><tr><th>Nota fiscal</th><th class="num">Bruto</th><th class="num">IR</th><th class="num">ISS</th><th class="num">INSS</th><th class="num">Outros</th><th class="num">Líquido</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($notas as $i => $n): $nid = (int) $n['id']; ?>
        <tr>
            <td><?= e($n['nota_fiscal']) ?></td>
            <td class="num"><?= $m((int) $n['bruto_centavos']) ?></td><td class="num"><?= $m((int) $n['ir_centavos']) ?></td>
            <td class="num"><?= $m((int) $n['iss_centavos']) ?></td><td class="num"><?= $m((int) $n['inss_centavos']) ?></td>
            <td class="num"><?= $m((int) $n['outros_centavos']) ?></td><td class="num"><strong><?= $m($n['liquido']) ?></strong></td>
            <td class="acoes">
                <a href="<?= e($base) ?>/conciliacao/notas/<?= $nid ?>/editar">Editar</a>
                <form method="post" action="<?= e($base) ?>/conciliacao/notas/<?= $nid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="cima"><button class="link" type="submit" aria-label="Mover para cima" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
                <form method="post" action="<?= e($base) ?>/conciliacao/notas/<?= $nid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="baixo"><button class="link" type="submit" aria-label="Mover para baixo" <?= $i === count($notas) - 1 ? 'disabled' : '' ?>>↓</button></form>
                <form method="post" action="<?= e($base) ?>/conciliacao/notas/<?= $nid ?>/excluir" onsubmit="return confirm('Excluir esta nota fiscal?')"><?= csrf_field() ?><button class="link perigo" type="submit">Excluir</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th>Total (bruto)</th><th class="num"><?= $m($totais['despesas']) ?></th><th colspan="6"></th></tr></tfoot>
</table></div>
<?php endif; ?>

<h2 id="dados" style="margin-top:2rem">3. Devoluções, extrato bancário e textos</h2>
<form method="post" action="<?= e($base) ?>/conciliacao/config" class="form">
    <?= csrf_field() ?>
    <fieldset>
        <legend>Textos do documento</legend>
        <label>Título (2ª linha do cabeçalho)
            <input name="titulo" maxlength="200" value="<?= $v('titulo') ?>">
        </label>
        <div class="linha">
            <label>Cabeçalho da coluna do extrato <input name="rotulo_extrato" maxlength="60" value="<?= $v('rotulo_extrato') ?>"></label>
            <label>Banco / agência / conta <input name="extrato_descricao" maxlength="200" value="<?= $v('extrato_descricao') ?>"></label>
        </div>
    </fieldset>
    <fieldset>
        <legend>Devoluções (R$) <small>(em branco = 0; não entram no total de despesas)</small></legend>
        <?php foreach (ConciliacaoCalculator::DEVOLUCOES as $campo => $rotulo): ?>
            <label><?= e($rotulo) ?> <input name="<?= e($campo) ?>" inputmode="decimal" placeholder="0,00" value="<?= $vm($campo) ?>"></label>
        <?php endforeach; ?>
    </fieldset>
    <fieldset>
        <legend>Extrato bancário</legend>
        <label>Data do saldo <input type="date" name="data_saldo" value="<?= $v('data_saldo') ?>"></label>
        <div class="linha">
            <label>Saldo da conta corrente (R$) <input name="saldo_cc" inputmode="decimal" placeholder="0,00" value="<?= $vm('saldo_cc') ?>"></label>
            <label>Saldo da conta aplicação/investimentos (R$) <input name="saldo_aplic" inputmode="decimal" placeholder="0,00" value="<?= $vm('saldo_aplic') ?>"></label>
        </div>
    </fieldset>
    <div class="botoes"><button class="btn" type="submit">Salvar dados</button></div>
</form>
