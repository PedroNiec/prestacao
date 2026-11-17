<?php /* Guia visual: página estática, somente leitura, sem dados nem formulários enviáveis. */ ?>
<ol class="migalhas" aria-label="Você está em">
    <li><a href="/projetos">Projetos</a></li>
    <li aria-current="page">Guia visual</li>
</ol>
<div class="barra">
    <h1>Guia visual</h1>
    <a class="btn secundario" href="/projetos">Voltar aos projetos</a>
</div>
<p>Todos os componentes da interface, em todos os estados, numa só página. Use-a para conferir o visual:
   <strong>redimensione a janela</strong> (ou use as ferramentas do navegador, largura ≈ 360 px), percorra tudo com a tecla
   <strong>Tab</strong> e confira se o foco fica sempre visível.</p>

<ul class="guia-nav">
    <li><a href="#cores">Cores</a></li><li><a href="#tipografia">Tipografia</a></li><li><a href="#botoes">Botões</a></li>
    <li><a href="#formularios">Formulários</a></li><li><a href="#tabelas">Tabelas</a></li><li><a href="#etiquetas">Etiquetas</a></li>
    <li><a href="#mensagens">Mensagens</a></li><li><a href="#cartoes">Cartões e indicadores</a></li><li><a href="#navegacao">Navegação</a></li>
    <li><a href="#listas">Lista de arquivos</a></li>
</ul>

<h2 id="cores">Cores</h2>
<p class="dica">Todas as cores vêm de variáveis (tokens) em <code>app.css</code>. Contraste de texto ≥ 4,5:1 e de bordas/foco ≥ 3:1, verificado por teste automático.</p>
<div class="guia-grade">
    <div class="amostra"><span class="amostra-cor sw-primaria"></span><span>Primária<code>--cor-primaria #0d7480</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-primaria-hover"></span><span>Primária (hover)<code>--cor-primaria-hover #0a5a63</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-primaria-suave"></span><span>Primária suave<code>--cor-primaria-suave #e3f1f3</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-fundo"></span><span>Fundo da página<code>--fundo #f7f9fa</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-superficie"></span><span>Superfície<code>--superficie #ffffff</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-texto"></span><span>Texto<code>--texto #1f3038</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-texto-suave"></span><span>Texto suave<code>--texto-suave #586670</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-borda-suave"></span><span>Borda suave (decorativa)<code>--borda-suave #d5dbe1</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-borda-campo"></span><span>Borda de campo<code>--borda-campo #7b8794</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-sucesso"></span><span>Sucesso<code>#e7f5ea / #166534</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-aviso"></span><span>Aviso<code>#fff3cd / #7a5b00</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-perigo"></span><span>Perigo<code>#fdecea / #9b1c1c</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-info"></span><span>Informação<code>#e3f1f3 / #0a5a63</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-neutro"></span><span>Neutro<code>#e4e7eb / #3e4c59</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-perigo-solido"></span><span>Perigo sólido (botão)<code>--perigo-solido #b3261e</code></span></div>
    <div class="amostra"><span class="amostra-cor sw-tabela-cab"></span><span>Cabeçalho de tabela<code>--tabela-cab #eef2f6</code></span></div>
</div>

<h2 id="tipografia">Tipografia</h2>
<div class="cartao">
    <h1>Título 1 — 30 px</h1>
    <h2>Título 2 — 20 px</h2>
    <h3>Título 3 — 16 px</h3>
    <p>Texto de corpo em 16 px, altura de linha 1,5. Exemplo de <a href="#tipografia">link</a> e de <strong>texto em destaque</strong>.</p>
    <p><small>Texto pequeno (14 px) para observações.</small></p>
    <p class="dica">Texto de ajuda (<code>.dica</code>) em cor suave.</p>
</div>

<h2 id="botoes">Botões</h2>
<div class="cartao">
    <div class="guia-demo">
        <button class="btn" type="button">Primário</button>
        <button class="btn secundario" type="button">Secundário</button>
        <button class="btn perigo" type="button">Perigo</button>
        <a class="btn" href="#botoes">Link como botão</a>
    </div>
    <div class="guia-demo">
        <button class="btn pequeno" type="button">Pequeno</button>
        <button class="btn secundario pequeno" type="button">Pequeno secundário</button>
        <button class="link" type="button">Ação discreta</button>
        <button class="link perigo" type="button">Excluir (discreta)</button>
    </div>
    <div class="guia-demo">
        <button class="btn" type="button" disabled>Primário desabilitado</button>
        <button class="btn secundario" type="button" disabled>Secundário desabilitado</button>
        <button class="link" type="button" disabled>Discreta desabilitada</button>
    </div>
    <p class="dica">Passe o mouse e use Tab para ver os estados de <em>hover</em> e de foco.</p>
</div>

<h2 id="formularios">Formulários</h2>
<div class="form">
    <div class="linha">
        <label>Campo obrigatório <input required placeholder="Digite algo"></label>
        <label>Campo opcional <input placeholder="Opcional"></label>
    </div>
    <div class="linha">
        <label>Valor (R$) <input inputmode="decimal" placeholder="1.234,56"></label>
        <label>Data <input type="date"></label>
        <label>Seleção
            <select><option>Convênio</option><option>Rendimentos</option><option>Contrapartida</option></select>
        </label>
    </div>
    <label>Descrição <textarea rows="3" placeholder="Texto mais longo"></textarea></label>
    <fieldset>
        <legend>Tipo <small>(grupo de opções)</small></legend>
        <label class="inline"><input type="radio" name="guia-tipo" checked> Receita</label>
        <label class="inline"><input type="radio" name="guia-tipo"> Despesa</label>
        <label class="inline"><input type="checkbox"> Caixa de seleção</label>
        <p class="dica">Texto de ajuda dentro de um grupo.</p>
    </fieldset>
    <label>Arquivo <input type="file"></label>
    <div class="botoes">
        <button class="btn" type="button">Salvar</button>
        <button class="btn secundario" type="button">Salvar e adicionar outro</button>
        <a href="#formularios">Cancelar</a>
    </div>
</div>

<h2 id="tabelas">Tabelas</h2>
<div class="rolagem">
<table>
    <thead><tr><th>Descrição</th><th>Origem</th><th class="num">Receita</th><th class="num">Despesa</th><th></th></tr></thead>
    <tbody>
        <tr><td>Transferência recebida</td><td>Convênio</td><td class="num">497.152,49</td><td class="num"></td>
            <td class="acoes"><a href="#tabelas">Editar</a><button class="link perigo" type="button">Excluir</button></td></tr>
        <tr><td>Pagamento ao fornecedor</td><td>Convênio</td><td class="num"></td><td class="num">491.186,66</td>
            <td class="acoes"><a href="#tabelas">Editar</a><button class="link perigo" type="button">Excluir</button></td></tr>
        <tr><td>Retenção de IRRF</td><td>Convênio</td><td class="num"></td><td class="num">5.965,83</td>
            <td class="acoes"><a href="#tabelas">Editar</a><button class="link perigo" type="button">Excluir</button></td></tr>
    </tbody>
    <tfoot><tr><th colspan="2">Total</th><th class="num">497.152,49</th><th class="num">497.152,49</th><th></th></tr></tfoot>
</table>
</div>
<p class="dica">Números alinhados à direita com algarismos de largura fixa; em telas estreitas a tabela rola dentro do próprio quadro.</p>

<h2 id="etiquetas">Etiquetas</h2>
<div class="cartao guia-demo">
    <span class="tag">Neutra</span>
    <span class="tag vazio">Vazio</span>
    <span class="tag ok">Enviado</span>
    <span class="tag aviso-tag">Desatualizado</span>
    <span class="tag erro">Erro</span>
    <span class="tag info">Informação</span>
</div>

<h2 id="mensagens">Mensagens</h2>
<p class="dica">Mensagens de sucesso e erro aparecem fixas no topo da janela depois de uma ação. Aqui, uma amostra de cada variante (clique no × para fechar).</p>
<div class="msg msg-ok msg-inline" role="status"><span class="msg-texto">Sucesso: o documento foi salvo.</span><button type="button" class="msg-fechar" aria-label="Fechar mensagem">&times;</button></div>
<div class="msg msg-erro msg-inline" role="alert"><span class="msg-texto">Erro: o valor informado é inválido.</span><button type="button" class="msg-fechar" aria-label="Fechar mensagem">&times;</button></div>
<div class="msg msg-aviso msg-inline" role="status"><span class="msg-texto">Aviso: a conciliação não fechou.</span><button type="button" class="msg-fechar" aria-label="Fechar mensagem">&times;</button></div>
<div class="msg msg-info msg-inline" role="status"><span class="msg-texto">Informação: nenhum documento é obrigatório.</span><button type="button" class="msg-fechar" aria-label="Fechar mensagem">&times;</button></div>
<h3>Estados da página</h3>
<p class="aviso">Aviso legado (<code>.aviso</code>), ainda usado por telas existentes.</p>
<div class="estado-vazio"><strong>Nenhum lançamento ainda</strong>Estado vazio: explica o que fazer a seguir.</div>

<h2 id="cartoes">Cartões e indicadores</h2>
<div class="kpis">
    <div class="kpi"><span class="kpi-rotulo">Receitas</span><span class="kpi-valor">497.152,49</span></div>
    <div class="kpi"><span class="kpi-rotulo">Despesas</span><span class="kpi-valor">497.152,49</span></div>
    <div class="kpi ok"><span class="kpi-rotulo">Conciliação</span><span class="kpi-valor">0,00 · fechou</span></div>
    <div class="kpi aviso"><span class="kpi-rotulo">Desatualizado</span><span class="kpi-valor">2 documentos</span></div>
    <div class="kpi erro"><span class="kpi-rotulo">Diferença</span><span class="kpi-valor">3.000,00</span></div>
</div>
<div class="guia-bloco"><div class="caixa">Cartão simples (<code>.caixa</code> / <code>.cartao</code>): agrupa conteúdo relacionado.</div></div>
<dl class="dados">
    <dt>Executor</dt><dd>Caxambu do Sul - SC</dd>
    <dt>Concedente</dt><dd>450001 - Secretaria de Estado da Educação (SED)</dd>
    <dt>Objeto</dt><dd>Aquisição de um veículo ônibus para o transporte escolar</dd>
</dl>

<h2 id="navegacao">Navegação e contexto</h2>
<ol class="migalhas" aria-label="Exemplo de trilha">
    <li><a href="#navegacao">Projetos</a></li><li><a href="#navegacao">Caxambu do Sul</a></li><li aria-current="page">Balancete</li>
</ol>
<div class="contexto"><strong>Caxambu do Sul - SC</strong><span>Aquisição de um veículo ônibus para o transporte escolar</span><span>Processo SCC 7909/2025</span></div>
<div class="barra"><h3>Barra de página</h3><button class="btn" type="button">Ação principal</button></div>

<h2 id="listas">Lista de arquivos (junção)</h2>
<ol class="lista-arquivos">
    <li><span class="alca" aria-hidden="true">⠿</span><span class="nome">NF 2797.pdf</span><small>1,2 MB</small><a href="#listas">Ver</a><button class="link" type="button">↑</button><button class="link" type="button" disabled>↓</button><button class="link perigo" type="button">Remover</button></li>
    <li><span class="alca" aria-hidden="true">⠿</span><span class="nome">NF 2810.pdf</span><small>0,8 MB</small><a href="#listas">Ver</a><button class="link" type="button">↑</button><button class="link" type="button">↓</button><button class="link perigo" type="button">Remover</button></li>
</ol>
