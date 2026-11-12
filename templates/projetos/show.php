<?php
use App\Catalog\DocumentCatalog;
?>
<div class="barra">
    <h1>Projeto #<?= (int) $projeto['id'] ?></h1>
    <div class="acoes">
        <a class="btn secundario" href="/projetos/<?= (int) $projeto['id'] ?>/editar">Editar dados</a>
        <form method="post" action="/projetos/<?= (int) $projeto['id'] ?>/excluir" onsubmit="return confirm('Excluir este projeto e todos os seus documentos?')">
            <?= csrf_field() ?>
            <button class="btn perigo" type="submit">Excluir</button>
        </form>
    </div>
</div>

<dl class="dados">
    <dt>Executor</dt><dd><?= e($projeto['municipio_nome']) ?> - <?= e($projeto['municipio_uf']) ?></dd>
    <dt>Concedente</dt><dd><?= e($projeto['concedente']) ?: '—' ?></dd>
    <dt>Processo</dt><dd><?= e($projeto['processo']) ?: '—' ?></dd>
    <dt>PP / NE / NL</dt><dd><?= e($projeto['nr_pp']) ?: '—' ?> / <?= e($projeto['nr_ne']) ?: '—' ?> / <?= e($projeto['nr_nl']) ?: '—' ?></dd>
    <dt>Objeto</dt><dd><?= e($projeto['objeto']) ?></dd>
    <dt>Local e data</dt><dd><?= e($projeto['local']) ?: '—' ?><?= $projeto['data_assinatura'] !== '' ? ' — ' . e(date('d/m/Y', strtotime($projeto['data_assinatura']))) : '' ?></dd>
    <dt>Assinaturas</dt><dd>
        <?= e($projeto['contador_nome']) ?: '—' ?><?= $projeto['contador_crc'] !== '' ? ' (' . e($projeto['contador_crc']) . ')' : '' ?>
        e <?= e($projeto['prefeito_nome']) ?: '—' ?> (Prefeito)
    </dd>
</dl>

<h2>Documentos da prestação de contas</h2>
<p class="aviso">Nenhum documento é obrigatório. O formulário da relação de bens será habilitado na próxima etapa.</p>
<div class="rolagem"><table>
    <thead><tr><th>Nº</th><th>Documento</th><th>Como é preenchido</th><th>Situação</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($documentos as $d):
        $env = $enviados[$d['codigo']] ?? null;
        $base = '/projetos/' . (int) $projeto['id'] . '/documentos/' . $d['codigo'];
        $upload = $d['comportamento'] === DocumentCatalog::UPLOAD;
        $juncao = $d['comportamento'] === DocumentCatalog::MERGE;
        $converte = DocumentCatalog::ehConversao($d['codigo']);
        $relatorio = $d['comportamento'] === DocumentCatalog::REPORT;
        $rel = $relatorios[$d['codigo']] ?? null;  // formulários já implementados
        $relatorioPronto = $rel !== null;
        $nLanc = (int) ($rel['n'] ?? 0);
        $ext = DocumentCatalog::extensoesUpload($d['codigo'])[0];
        $accept = ['pdf' => 'application/pdf,.pdf', 'png' => 'image/png,.png', 'pptx' => '.pptx,application/vnd.openxmlformats-officedocument.presentationml.presentation'][$ext];
        $nArq = (int) ($contagens[$d['codigo']] ?? 0);
    ?>
        <tr id="doc-<?= e($d['codigo']) ?>">
            <td><?= e($d['codigo']) ?></td>
            <td><?= e($d['nome']) ?></td>
            <td><?= e(DocumentCatalog::label($d['comportamento'])) ?></td>
            <td>
                <?php if ($juncao): ?>
                    <?php if ($env && (int) $env['desatualizado'] === 1): ?>
                        <span class="tag aviso-tag">Desatualizado</span>
                    <?php elseif ($env): ?>
                        <span class="tag ok">Gerado</span>
                    <?php elseif ($nArq > 0): ?>
                        <span class="tag aviso-tag">Não gerado</span>
                    <?php else: ?>
                        <span class="tag vazio">Vazio</span>
                    <?php endif; ?>
                    <?php if ($nArq > 0): ?><small class="arq"><?= $nArq ?> arquivo(s)</small><?php endif; ?>
                <?php elseif ($relatorio && $relatorioPronto): ?>
                    <?php if ($env && (int) $env['desatualizado'] === 1): ?>
                        <span class="tag aviso-tag">Desatualizado</span>
                    <?php elseif ($env): ?>
                        <span class="tag ok">Gerado</span>
                    <?php elseif ($nLanc > 0): ?>
                        <span class="tag aviso-tag">Não gerado</span>
                    <?php else: ?>
                        <span class="tag vazio">Vazio</span>
                    <?php endif; ?>
                    <?php if ($nLanc > 0): ?><small class="arq"><?= $nLanc ?> <?= e($rel['unidade'] ?? '') ?></small><?php endif; ?>
                <?php elseif ($env): ?>
                    <span class="tag ok"><?= $converte ? 'Convertido' : 'Enviado' ?></span>
                    <small class="arq"><?= e($env['nome_original']) ?> · <?= e(format_bytes((int) $env['tamanho'])) ?></small>
                <?php else: ?>
                    <span class="tag vazio">Vazio</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($upload || $converte): ?>
                    <?php if ($env): ?>
                        <div class="acoes">
                            <a href="<?= e($base) ?>/baixar?ver=1" target="_blank" rel="noopener">Ver</a>
                            <a href="<?= e($base) ?>/baixar">Baixar</a>
                            <form method="post" action="<?= e($base) ?>/remover" onsubmit="return confirm('Remover este arquivo?')">
                                <?= csrf_field() ?>
                                <button class="link perigo" type="submit">Remover</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= e($base) ?>/enviar" enctype="multipart/form-data" class="envio">
                        <?= csrf_field() ?>
                        <input type="file" name="arquivo" accept="<?= e($accept) ?>" required aria-label="Arquivo <?= e(strtoupper($ext)) ?> do documento <?= e($d['codigo']) ?>">
                        <button class="btn secundario" type="submit"><?= $env ? 'Substituir' : 'Enviar' ?></button>
                    </form>
                    <?php if ($converte): ?><small class="breve">Envie <?= e(strtoupper($ext)) ?>; o sistema converte para PDF.</small><?php endif; ?>
                <?php elseif ($relatorio && $relatorioPronto): ?>
                    <div class="acoes">
                        <a class="btn secundario" href="<?= e($rel['url']) ?>"><?= e($rel['rotulo']) ?></a>
                        <?php if ($env): ?><a href="<?= e($base) ?>/baixar">Baixar</a><?php endif; ?>
                    </div>
                <?php elseif ($juncao): ?>
                    <div class="acoes">
                        <a class="btn secundario" href="<?= e($base) ?>">Gerenciar arquivos</a>
                        <?php if ($env): ?><a href="<?= e($base) ?>/baixar">Baixar</a><?php endif; ?>
                    </div>
                <?php else: ?>
                    <small class="breve">Disponível em etapa futura</small>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
