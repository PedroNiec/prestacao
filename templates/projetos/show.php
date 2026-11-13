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
    <div class="projeto-ident">
        <span class="icone-pasta" aria-hidden="true"></span>
        <div class="projeto-titulo">
            <h1><?= trim($projeto['objeto']) !== '' ? e($projeto['objeto']) : 'Projeto #' . $pid ?></h1>
            <p class="projeto-sub"><?= e($projeto['municipio_nome']) ?> - <?= e($projeto['municipio_uf']) ?> · Projeto #<?= $pid ?></p>
            <p class="dica projeto-zip">
            <?php if ($zip['comPdf'] === 0): ?>
                Nenhum documento com PDF ainda: o ZIP fica disponível quando houver pelo menos um.
            <?php else: ?>
                <?= $zip['comPdf'] ?> documento(s) no ZIP<?php if ($zip['desatualizados']): ?> · <span class="tag aviso-tag"><?= count($zip['desatualizados']) ?> desatualizado(s)</span> (<?= e(implode(', ', $zip['desatualizados'])) ?>)<?php endif; ?><?php if ($zip['naoGerados']): ?> · <span class="tag aviso-tag"><?= count($zip['naoGerados']) ?> não gerado(s)</span> (<?= e(implode(', ', $zip['naoGerados'])) ?>)<?php endif; ?>
            <?php endif; ?>
            </p>
        </div>
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

<section class="cartao projeto-dados" aria-labelledby="dados-titulo">
    <h2 id="dados-titulo" class="so-leitor">Dados do projeto</h2>
    <dl class="dados-faixa">
        <div><dt>Concedente</dt><dd><?= $valor($projeto['concedente']) ?></dd></div>
        <div><dt>Processo</dt><dd><?= $valor($projeto['processo']) ?></dd></div>
        <div><dt>PP</dt><dd><?= $valor($projeto['nr_pp']) ?></dd></div>
        <div><dt>NE</dt><dd><?= $valor($projeto['nr_ne']) ?></dd></div>
        <div><dt>NL</dt><dd><?= $valor($projeto['nr_nl']) ?></dd></div>
        <div><dt>Local e data</dt><dd><?= $localData ?></dd></div>
        <div><dt>Contador</dt><dd><?= $contador ?></dd></div>
        <div><dt>Prefeito</dt><dd><?= $valor($projeto['prefeito_nome']) ?></dd></div>
    </dl>
</section>

<?php
/** Painel de envio (substituir ou enviar): mesmo formulário e contrato de antes; só aparece quando o <details> é aberto. */
$painelEnvio = function (array $l, array $u): void { ?>
<div class="recolhe-painel">
<form method="post" action="<?= e($u['enviar']) ?>" enctype="multipart/form-data" class="envio">
<?= csrf_field() ?>
<input type="file" name="arquivo" accept="<?= e($l['envio']['accept']) ?>" required aria-label="<?= e($l['envio']['rotuloCampo']) ?>">
<button class="btn pequeno" type="submit"><?= $l['temArquivo'] ? 'Substituir' : 'Enviar' ?></button>
</form>
<?php if ($l['envio']['dicaConversao'] !== null): ?><small class="breve"><?= e($l['envio']['dicaConversao']) ?></small><?php endif; ?>
</div>
<?php };
?>
<section aria-labelledby="docs-titulo">
    <h2 id="docs-titulo">Documentos da prestação de contas</h2>
    <p class="dica">Nenhum documento é obrigatório.</p>
    <ul class="doc-lista">
<?php /* laço sem indentação: os espaços se repetiriam nas 25 linhas do HTML gerado */ ?>
<?php foreach ($linhas as $l): $u = $l['urls']; $rotuloMenu = 'Mais ações para o documento ' . $l['codigo'] . ' — ' . $l['nome']; ?>
<li id="doc-<?= e($l['codigo']) ?>" class="doc-item<?= $l['desatualizado'] ? ' doc-pendente' : '' ?>">
<span class="doc-num"><?= e($l['codigo']) ?></span>
<div class="doc-corpo"><span class="doc-nome"><?= e($l['nome']) ?></span> <small class="doc-tipo"><?= e($l['tipo']) ?></small></div>
<div class="doc-situacao"><span class="tag <?= e($l['situacao']['classe']) ?>"><?= e($l['situacao']['texto']) ?></span>
<?php if ($l['detalhe'] !== null): ?><small class="arq"><?= e($l['detalhe']) ?></small><?php endif; ?></div>
<div class="doc-acoes">
<?php if ($l['acao'] === L::ACAO_UPLOAD && !$l['temArquivo']): ?>
<details class="recolhe doc-envio">
<summary class="btn secundario pequeno">Enviar arquivo</summary>
<?php $painelEnvio($l, $u); ?>
</details>
<?php elseif ($l['acao'] === L::ACAO_UPLOAD): ?>
<a class="btn secundario pequeno" href="<?= e($u['ver']) ?>" target="_blank" rel="noopener">Ver</a>
<details class="menu-mais">
<summary aria-label="<?= e($rotuloMenu) ?>">⋮</summary>
<div class="menu-mais-painel">
<a href="<?= e($u['baixar']) ?>">Baixar</a>
<details class="recolhe">
<summary class="menu-item">Substituir</summary>
<?php $painelEnvio($l, $u); ?>
</details>
<form method="post" action="<?= e($u['remover']) ?>" onsubmit="return confirm('Remover este arquivo?')">
<?= csrf_field() ?>
<button class="link perigo" type="submit">Remover</button>
</form>
</div>
</details>
<?php elseif ($l['acao'] === L::ACAO_FORMULARIO || $l['acao'] === L::ACAO_JUNCAO): ?>
<?php if ($l['acao'] === L::ACAO_FORMULARIO): ?>
<a class="btn secundario pequeno" href="<?= e($l['formulario']['url']) ?>"><?= e($l['formulario']['rotulo']) ?></a>
<?php else: ?>
<a class="btn secundario pequeno" href="<?= e($u['base']) ?>">Gerenciar arquivos</a>
<?php endif; ?>
<?php if ($l['temArquivo']): ?>
<details class="menu-mais">
<summary aria-label="<?= e($rotuloMenu) ?>">⋮</summary>
<div class="menu-mais-painel">
<a href="<?= e($u['baixar']) ?>">Baixar</a>
</div>
</details>
<?php endif; ?>
<?php endif; ?>
</div>
</li>
<?php endforeach; ?>
    </ul>
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
