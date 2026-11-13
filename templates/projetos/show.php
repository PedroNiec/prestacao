<?php
use App\Presenters\DocumentoLinhaPresenter as L;

$pid = (int) $projeto['id'];
$valor = fn ($v): string => trim((string) $v) !== '' ? e((string) $v) : '—';
$data = $projeto['data_assinatura'] !== '' ? date('d/m/Y', strtotime($projeto['data_assinatura'])) : '';
$localData = trim($projeto['local']) !== '' || $data !== ''
    ? trim(e($projeto['local']) . ($projeto['local'] !== '' && $data !== '' ? ' — ' : '') . e($data))
    : '—';
// ZIP da prestação (specs/zip-prestacao.md): pendências avisadas antes de baixar (RF-Z04, DEC-Z2)
$zip = L::resumoZip($linhas);
$avisos = [];
if ($zip['desatualizados']) {
    $avisos[] = count($zip['desatualizados']) . ' documento(s) desatualizado(s): ' . implode(', ', $zip['desatualizados']) . '.';
}
if ($zip['naoGerados']) {
    $avisos[] = count($zip['naoGerados']) . ' documento(s) não gerado(s): ' . implode(', ', $zip['naoGerados']) . ' (não entram no ZIP).';
}
$confirmar = $avisos ? implode("
", $avisos) . "

Baixar mesmo assim?" : null;
$contador = $valor($projeto['contador_nome']) . ($projeto['contador_crc'] !== '' ? ' <small>(' . e($projeto['contador_crc']) . ')</small>' : '');
?>
<nav aria-label="Trilha de navegação">
    <ol class="migalhas">
        <li><a href="/projetos">Projetos</a></li>
        <li aria-current="page">Projeto #<?= $pid ?></li>
    </ol>
</nav>

<div class="barra projeto-cab">
    <div class="projeto-titulo">
        <h1><?= trim($projeto['objeto']) !== '' ? e($projeto['objeto']) : 'Projeto #' . $pid ?></h1>
        <p class="projeto-sub"><?= e($projeto['municipio_nome']) ?> - <?= e($projeto['municipio_uf']) ?> · Projeto #<?= $pid ?></p>
    </div>
    <div class="acoes">
        <a class="btn secundario" href="/projetos/<?= $pid ?>/editar">Editar dados</a>
        <?php if ($zip['comPdf'] > 0): ?>
            <a class="btn" href="/projetos/<?= $pid ?>/zip"<?php if ($confirmar !== null): ?> onclick="return confirm(<?= e((string) json_encode($confirmar, JSON_UNESCAPED_UNICODE)) ?>)"<?php endif; ?>>Baixar prestação (ZIP)</a>
        <?php else: ?>
            <span class="btn" aria-disabled="true" title="Nenhum documento com PDF ainda">Baixar prestação (ZIP)</span>
        <?php endif; ?>
    </div>
</div>
<p class="dica">
<?php if ($zip['comPdf'] === 0): ?>
    Nenhum documento com PDF ainda: o ZIP fica disponível quando houver pelo menos um.
<?php else: ?>
    <?= $zip['comPdf'] ?> documento(s) no ZIP<?php if ($zip['desatualizados']): ?> · <span class="tag aviso-tag"><?= count($zip['desatualizados']) ?> desatualizado(s)</span> (<?= e(implode(', ', $zip['desatualizados'])) ?>)<?php endif; ?><?php if ($zip['naoGerados']): ?> · <span class="tag aviso-tag"><?= count($zip['naoGerados']) ?> não gerado(s)</span> (<?= e(implode(', ', $zip['naoGerados'])) ?>)<?php endif; ?>
<?php endif; ?>
</p>

<section class="cartao projeto-dados" aria-labelledby="dados-titulo">
    <h2 id="dados-titulo" class="cartao-titulo">Dados do projeto</h2>
    <dl class="dados-grade">
        <div class="dado-largo"><dt>Concedente</dt><dd><?= $valor($projeto['concedente']) ?></dd></div>
        <div class="dado-largo"><dt>Processo</dt><dd><?= $valor($projeto['processo']) ?></dd></div>
        <div><dt>PP</dt><dd><?= $valor($projeto['nr_pp']) ?></dd></div>
        <div><dt>NE</dt><dd><?= $valor($projeto['nr_ne']) ?></dd></div>
        <div><dt>NL</dt><dd><?= $valor($projeto['nr_nl']) ?></dd></div>
        <div><dt>Local e data</dt><dd><?= $localData ?></dd></div>
        <div><dt>Contador</dt><dd><?= $contador ?></dd></div>
        <div><dt>Prefeito</dt><dd><?= $valor($projeto['prefeito_nome']) ?></dd></div>
    </dl>
</section>

<section aria-labelledby="docs-titulo">
    <h2 id="docs-titulo">Documentos da prestação de contas</h2>
    <p class="dica">Nenhum documento é obrigatório.</p>
    <div class="rolagem">
    <table class="docs" role="table">
        <thead role="rowgroup">
            <tr role="row">
                <th role="columnheader" scope="col" class="docs-num">Nº</th>
                <th role="columnheader" scope="col">Documento</th>
                <th role="columnheader" scope="col">Situação</th>
                <th role="columnheader" scope="col">Ações</th>
            </tr>
        </thead>
        <tbody role="rowgroup">
<?php /* laço sem indentação: os espaços se repetiriam nas 25 linhas do HTML gerado */ ?>
<?php foreach ($linhas as $l): $u = $l['urls']; ?>
<tr id="doc-<?= e($l['codigo']) ?>" role="row" class="doc-linha<?= $l['desatualizado'] ? ' doc-pendente' : '' ?>">
<td role="cell" class="docs-num"><?= e($l['codigo']) ?></td>
<td role="cell"><span class="doc-nome"><?= e($l['nome']) ?></span> <small class="doc-tipo"><?= e($l['tipo']) ?></small></td>
<td role="cell" class="docs-situacao" data-rotulo="Situação"><span class="tag <?= e($l['situacao']['classe']) ?>"><?= e($l['situacao']['texto']) ?></span>
<?php if ($l['detalhe'] !== null): ?><small class="arq"><?= e($l['detalhe']) ?></small><?php endif; ?></td>
<td role="cell" class="docs-acoes"><div class="acoes">
<?php if ($l['acao'] === L::ACAO_UPLOAD): ?>
<?php if ($l['temArquivo']): ?>
<a href="<?= e($u['ver']) ?>" target="_blank" rel="noopener">Ver</a>
<a href="<?= e($u['baixar']) ?>">Baixar</a>
<?php endif; ?>
<details class="recolhe">
<summary class="btn secundario pequeno"><?= $l['temArquivo'] ? 'Substituir' : 'Enviar arquivo' ?></summary>
<div class="recolhe-painel">
<form method="post" action="<?= e($u['enviar']) ?>" enctype="multipart/form-data" class="envio">
<?= csrf_field() ?>
<input type="file" name="arquivo" accept="<?= e($l['envio']['accept']) ?>" required aria-label="<?= e($l['envio']['rotuloCampo']) ?>">
<button class="btn pequeno" type="submit"><?= $l['temArquivo'] ? 'Substituir' : 'Enviar' ?></button>
</form>
<?php if ($l['envio']['dicaConversao'] !== null): ?><small class="breve"><?= e($l['envio']['dicaConversao']) ?></small><?php endif; ?>
</div>
</details>
<?php if ($l['temArquivo']): ?>
<form method="post" action="<?= e($u['remover']) ?>" onsubmit="return confirm('Remover este arquivo?')">
<?= csrf_field() ?>
<button class="link perigo" type="submit">Remover</button>
</form>
<?php endif; ?>
<?php elseif ($l['acao'] === L::ACAO_FORMULARIO): ?>
<a class="btn secundario pequeno" href="<?= e($l['formulario']['url']) ?>"><?= e($l['formulario']['rotulo']) ?></a>
<?php if ($l['temArquivo']): ?><a href="<?= e($u['baixar']) ?>">Baixar</a><?php endif; ?>
<?php elseif ($l['acao'] === L::ACAO_JUNCAO): ?>
<a class="btn secundario pequeno" href="<?= e($u['base']) ?>">Gerenciar arquivos</a>
<?php if ($l['temArquivo']): ?><a href="<?= e($u['baixar']) ?>">Baixar</a><?php endif; ?>
<?php endif; ?>
</div></td>
</tr>
<?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>

<section class="zona-perigo" aria-labelledby="perigo-titulo">
    <div>
        <h2 id="perigo-titulo">Zona de perigo</h2>
        <p>Excluir este projeto apaga todos os seus documentos. Não é possível desfazer.</p>
    </div>
    <form method="post" action="/projetos/<?= $pid ?>/excluir" onsubmit="return confirm('Excluir este projeto e todos os seus documentos?')">
        <?= csrf_field() ?>
        <button class="btn perigo" type="submit">Excluir projeto</button>
    </form>
</section>
