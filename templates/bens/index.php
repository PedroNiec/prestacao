<?php
use App\Core\View;
use App\Domain\Money;

$base = '/projetos/' . (int) $projeto['id'];
$rb = $base . '/relacao-de-bens';
$m = fn (int $v) => e(Money::format($v));
?>
<?= View::render('partes/trilha', ['projeto' => $projeto, 'ancora' => '#doc-09', 'itens' => [['09 Relação de bens']]], false) ?>
<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1>09 — Relação de bens</h1>
        <p class="projeto-sub"><?= e($projeto['municipio_nome']) ?> - <?= e($projeto['municipio_uf']) ?> · Projeto #<?= (int) $projeto['id'] ?></p>
    </div>
    <a class="btn" href="<?= e($rb) ?>/bens/novo">Novo bem</a>
</div>
<p class="dica">Bens adquiridos, produzidos ou construídos com os recursos do convênio. Descrição e valor são digitados por você; o total é a soma.</p>

<?= View::render('partes/pdf', [
    'final' => $final, 'gerar' => $rb . '/gerar', 'baixar' => $base . '/documentos/09/baixar',
    'textos' => ['vazio' => 'PDF não gerado', 'gerado' => 'PDF gerado', 'desatualizado' => 'Os bens ou o título mudaram desde a última geração.'],
], false) ?>

<?php if (!$bens): ?>
    <div class="estado-vazio">
        <strong>Nenhum bem cadastrado.</strong>
        Adicione os bens adquiridos, produzidos ou construídos.<br>
        <a class="btn" href="<?= e($rb) ?>/bens/novo">Novo bem</a>
    </div>
<?php else: ?>
<section class="cartao secao">
<h2>Bens (<?= count($bens) ?>)</h2>
<div class="rolagem"><table>
    <thead><tr><th>Item</th><th>Descrição</th><th class="num">Valor (R$)</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bens as $i => $b): $bid = (int) $b['id']; ?>
        <tr id="b<?= $bid ?>">
            <td><?= $i + 1 ?></td>
            <td><?= e($b['descricao']) ?></td>
            <td class="num"><?= $m((int) $b['valor_centavos']) ?></td>
            <td class="acoes">
                <a href="<?= e($rb) ?>/bens/<?= $bid ?>/editar">Editar</a>
                <form method="post" action="<?= e($rb) ?>/bens/<?= $bid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="cima"><button class="link" type="submit" aria-label="Mover para cima" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
                <form method="post" action="<?= e($rb) ?>/bens/<?= $bid ?>/mover"><?= csrf_field() ?><input type="hidden" name="direcao" value="baixo"><button class="link" type="submit" aria-label="Mover para baixo" <?= $i === count($bens) - 1 ? 'disabled' : '' ?>>↓</button></form>
                <form method="post" action="<?= e($rb) ?>/bens/<?= $bid ?>/excluir" onsubmit="return confirm('Excluir este bem?')"><?= csrf_field() ?><button class="link perigo" type="submit">Excluir</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="2">Total</th><th class="num"><?= $m($total) ?></th><th></th></tr></tfoot>
</table></div>
</section>
<?php endif; ?>

<h2 id="titulo">Título do documento</h2>
<form method="post" action="<?= e($rb) ?>/titulo" class="form">
    <?= csrf_field() ?>
    <label>Título que aparece no PDF
        <input name="titulo_documento" maxlength="200" value="<?= e($tituloDocumento) ?>">
    </label>
    <p class="dica">Em branco, volta ao padrão "RELAÇÃO DE BENS ADQUIRIDOS". Troque, por exemplo, por "RELAÇÃO DE BENS CONSTRUÍDOS" em obras.</p>
    <div class="botoes"><button class="btn" type="submit">Salvar título</button></div>
</form>
