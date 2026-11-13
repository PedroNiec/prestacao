<?php
use App\Domain\ConciliacaoCalculator;
use App\Domain\Money;
use App\Core\View;

$base = '/projetos/' . (int) $projeto['id'];
$m = fn (int $v) => e(Money::format($v));
$diferenca = $totais['conciliacao'] !== 0;
$v = fn (string $k) => e($config[$k] ?? '');
$vm = fn (string $k) => isset($config[$k]) && is_numeric($config[$k]) ? e(Money::input((int) $config[$k])) : e($config[$k] ?? '');
?>
<?= View::render("partes/trilha", ["projeto" => $projeto, "ancora" => "#doc-15", "itens" => [["15 Conciliação bancária"]]], false) ?>
<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1>15 — Conciliação bancária</h1>
        <p class="projeto-sub"><?= e($projeto["municipio_nome"]) ?> - <?= e($projeto["municipio_uf"]) ?> · Projeto #<?= (int) $projeto["id"] ?></p>
        <ul class="atalhos" aria-label="Seções desta página">
            <li><a href="#receitas">1. Receitas</a></li>
            <li><a href="#notas">2. Notas fiscais</a></li>
            <li><a href="#dados">3. Devoluções, extrato e textos</a></li>
        </ul>
    </div>
</div>

<?= View::render("partes/pdf", [
    "final" => $final, "gerar" => $base . "/conciliacao/gerar", "baixar" => $base . "/documentos/15/baixar",
    "textos" => ["vazio" => "PDF não gerado", "gerado" => "PDF gerado", "desatualizado" => "Os dados (ou o balancete) mudaram desde a última geração."],
], false) ?>

<h2 class="so-leitor">Resumo</h2>
<div class="kpis">
    <div class="kpi"><span class="kpi-rotulo">Total de receitas</span><span class="kpi-valor"><?= $m($totais["receitas"]) ?></span></div>
    <div class="kpi"><span class="kpi-rotulo">Total de despesas (bruto das notas)</span><span class="kpi-valor"><?= $m($totais["despesas"]) ?></span></div>
    <div class="kpi"><span class="kpi-rotulo">Saldo (receitas − despesas)</span><span class="kpi-valor"><?= $m($totais["saldo"]) ?></span></div>
    <div class="kpi"><span class="kpi-rotulo">Saldo em conta (corrente + aplicação)</span><span class="kpi-valor"><?= $m($totais["bancario"]) ?></span></div>
    <div class="kpi <?= $diferenca ? 'erro' : 'ok' ?>"><span class="kpi-rotulo">Conciliação (saldo − bancário)</span><span class="kpi-valor"><?= $m($totais["conciliacao"]) ?></span><span class="kpi-nota"><?= $diferenca ? "Não fechou" : "Fechou" ?></span></div>
</div>
<?php if ($diferenca): ?>
    <p class="aviso">A conciliação <strong>não fechou</strong>: diferença de R$ <?= $m($totais['conciliacao']) ?>.
    <?php if ($totais['devolucoes'] > 0): ?>
        As devoluções (R$ <?= $m($totais['devolucoes']) ?>) <strong>não entram</strong> no total de despesas, o que pode explicar a diferença.
    <?php endif; ?>
    </p>
<?php endif; ?>

<section class="cartao secao">
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
    <div class="botoes"><button class="btn" type="submit">Salvar receitas</button></div>
</form>
</section>
<?php foreach ($receitas as $i => $r): if ($r['alterado']): ?>
    <form method="post" id="restaurar-<?= $i ?>" action="<?= e($base) ?>/conciliacao/receitas/restaurar"><?= csrf_field() ?><input type="hidden" name="chave" value="<?= e($r['chave']) ?>"></form>
<?php endif; endforeach; ?>

<section class="cartao secao" id="notas">
<div class="secao-cab">
    <h2>2. Notas fiscais</h2>
    <a class="btn" href="<?= e($base) ?>/conciliacao/notas/novo">Nova nota fiscal</a>
</div>
<?php if (!$notas): ?>
    <div class="estado-vazio"><strong>Nenhuma nota fiscal.</strong> Cada nota gera um bloco com bruto, líquido e retenções.</div>
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
</section>

<h2 id="dados">3. Devoluções, extrato bancário e textos</h2>
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
