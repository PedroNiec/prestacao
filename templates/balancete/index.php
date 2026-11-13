<?php
use App\Domain\BalanceteCalculator;
use App\Domain\Dates;
use App\Domain\Money;
use App\Core\View;

$base = '/projetos/' . (int) $projeto['id'];
$o = $totais['origens'];
$novoId = (int) ($novoId ?? 0);
?>
<?= View::render("partes/trilha", ["projeto" => $projeto, "ancora" => "#doc-01", "itens" => [["01 Balancete"]]], false) ?>
<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1>01 — Balancete</h1>
        <p class="projeto-sub"><?= e($projeto["municipio_nome"]) ?> - <?= e($projeto["municipio_uf"]) ?> · Projeto #<?= (int) $projeto["id"] ?></p>
    </div>
    <a class="btn" href="#novo-lancamento">Novo lançamento</a>
</div>
<p class="dica">Receita é o que <strong>entrou</strong> na conta; despesa é o que <strong>saiu</strong>. Retenções (IRRF, ISS, INSS) são lançadas como linhas de despesa.</p>

<?= View::render("partes/pdf", [
    "final" => $final, "gerar" => $base . "/balancete/gerar", "baixar" => $base . "/documentos/01/baixar",
    "textos" => ["vazio" => "PDF não gerado", "gerado" => "PDF gerado", "desatualizado" => "Os lançamentos mudaram desde a última geração."],
], false) ?>

<section class="secao" id="novo-lancamento" aria-labelledby="novo-lancamento-titulo">
<form method="post" action="<?= e($base) ?>/balancete/lancamentos" class="form form-cheia" id="form-lancamento">
    <h2 id="novo-lancamento-titulo">Novo lançamento</h2>
    <?= csrf_field() ?>
    <?= View::render('balancete/_campos', ['l' => $novoForm, 'origens' => BalanceteCalculator::ORIGENS], false) ?>
    <div class="botoes"><button class="btn" type="submit">Salvar lançamento</button></div>
</form>
</section>

<?php if ($lancamentos): ?>
<div class="kpis">
    <div class="kpi"><span class="kpi-rotulo">Receita total</span><span class="kpi-valor"><?= e(Money::format($totais["receita"])) ?></span></div>
    <div class="kpi"><span class="kpi-rotulo">Despesa total</span><span class="kpi-valor"><?= e(Money::format($totais["despesa"])) ?></span></div>
    <div class="kpi"><span class="kpi-rotulo">Saldo (receita − despesa)</span><span class="kpi-valor"><?= e(Money::format($totais["saldo"])) ?></span></div>
</div>
<?php endif; ?>

<h2>Lançamentos (<?= count($lancamentos) ?>)</h2>
<?php if (!$lancamentos): ?>
    <div class="estado-vazio">
        <strong>Nenhum lançamento ainda.</strong>
        Comece lançando a transferência recebida (receita) no formulário acima.
    </div>
<?php else: ?>
<div class="rolagem rolagem-menu">
<table class="lanc">
    <thead><tr>
        <th>Descrição</th><th>Parc.</th><th>NF</th><th>Data</th><th>Fonte</th>
        <th class="num">Receita</th><th class="num">Despesa</th><th><span class="so-leitor">Ações</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($lancamentos as $i => $l): $lid = (int) $l['id'];
        $apoio = array_filter([
            $l['cnpj'] !== '' ? 'CNPJ ' . $l['cnpj'] : '',
            $l['nota_empenho'] !== '' ? 'Empenho ' . $l['nota_empenho'] : '',
            $l['doc'] !== '' ? 'DOC ' . $l['doc'] : '',
            BalanceteCalculator::ORIGENS[$l['origem']] ?? $l['origem'],
        ], fn ($t) => $t !== ''); ?>
        <tr id="l<?= $lid ?>"<?= $lid === $novoId ? ' class="linha-nova"' : '' ?>>
            <td class="lanc-desc"><?= e($l['descricao']) ?><?php if ($lid === $novoId): ?> <span class="tag info">novo</span><?php endif; ?>
                <small><?= e(implode(' · ', $apoio)) ?></small></td>
            <td><?= e($l['boletim_parcela']) ?></td>
            <td><?= e($l['nota_fiscal']) ?></td>
            <td class="nowrap"><?= e(Dates::br((string) $l['data'])) ?></td>
            <td><?= (int) $l['fonte'] ?></td>
            <td class="num lanc-rec"><?= $l['tipo'] === 'receita' ? e(Money::format((int) $l['valor_centavos'])) : '' ?></td>
            <td class="num lanc-desp"><?= $l['tipo'] === 'despesa' ? e(Money::format((int) $l['valor_centavos'])) : '' ?></td>
            <td class="lanc-menu">
                <details class="menu-mais">
                    <summary aria-label="Mais ações para o lançamento <?= $lid ?>">⋮</summary>
                    <div class="menu-mais-painel">
                        <a href="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/editar">Editar</a>
                        <form method="post" action="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="cima"><button class="link" type="submit" <?= $i === 0 ? 'disabled' : '' ?>>Mover para cima</button></form>
                        <form method="post" action="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="baixo"><button class="link" type="submit" <?= $i === count($lancamentos) - 1 ? 'disabled' : '' ?>>Mover para baixo</button></form>
                        <form method="post" action="<?= e($base) ?>/balancete/lancamentos/<?= $lid ?>/excluir" onsubmit="return confirm('Excluir este lançamento?')"><?= csrf_field() ?><button class="link perigo" type="submit">Excluir</button></form>
                    </div>
                </details>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="5">Total geral</th><th class="num"><?= e(Money::format($totais['receita'])) ?></th><th class="num"><?= e(Money::format($totais['despesa'])) ?></th><th></th></tr>
        <tr><th colspan="5">Saldo (receita − despesa)</th><th class="num" colspan="2"><?= e(Money::format($totais['saldo'])) ?></th><th></th></tr>
    </tfoot>
</table>
</div>

<section class="cartao secao">
<h2>Totais por origem</h2>
<table class="compacta">
    <thead><tr><th>Origem</th><th class="num">Receita</th><th class="num">Despesa</th></tr></thead>
    <tbody>
    <?php foreach (BalanceteCalculator::ORIGENS as $k => $nome): ?>
        <tr><td><?= e($nome) ?></td><td class="num"><?= e(Money::format($o[$k]['receita'])) ?></td><td class="num"><?= e(Money::format($o[$k]['despesa'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</section>
<?php endif; ?>
<script src="/assets/lancamento-form.js"></script>
