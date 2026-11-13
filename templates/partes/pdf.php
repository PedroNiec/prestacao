<?php
/**
 * Cartão "PDF do documento" (spec melhoria-visual-demais-telas, RF-T03), igual nas 4 telas que geram PDF.
 * $final: registro do documento gerado ou null · $gerar: URL do POST de geração · $baixar: URL de download
 * $textos: ['vazio' => etiqueta sem PDF, 'gerado' => etiqueta gerado, 'desatualizado' => frase explicativa]
 * $desabilitado (opcional): desabilita "Gerar PDF" (junção sem arquivos).
 */
$desatualizado = $final && (int) $final['desatualizado'] === 1;
?>
<section class="cartao cartao-pdf" aria-labelledby="pdf-titulo">
    <div>
        <h2 id="pdf-titulo" class="cartao-titulo">PDF do documento</h2>
        <p class="cartao-pdf-situacao">
        <?php if (!$final): ?>
            <span class="tag vazio"><?= e($textos['vazio']) ?></span>
        <?php elseif ($desatualizado): ?>
            <span class="tag aviso-tag">Desatualizado</span> <?= e($textos['desatualizado']) ?>
        <?php else: ?>
            <span class="tag ok"><?= e($textos['gerado']) ?></span> <small><?= e(format_bytes((int) $final['tamanho'])) ?> · <?= e($final['atualizado_em']) ?></small>
        <?php endif; ?>
        </p>
    </div>
    <div class="acoes">
        <form method="post" action="<?= e($gerar) ?>"><?= csrf_field() ?><button class="btn" type="submit"<?= !empty($desabilitado) ? ' disabled' : '' ?>>Gerar PDF</button></form>
        <?php if ($final): ?>
            <a href="<?= e($baixar) ?>?ver=1" target="_blank" rel="noopener">Ver</a>
            <a href="<?= e($baixar) ?>">Baixar</a>
        <?php endif; ?>
    </div>
</section>
