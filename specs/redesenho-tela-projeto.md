# SPEC — Redesenho visual: Tela do Projeto (fase 3.3)

**Versão:** 1.0
**Status:** Implementada (2026-11-13)
**Data:** 2026-11-13
**Relacionada a:** `specs/redesenho-layout-municipios.md` (padrão visual aprovado), `specs/redesenho-lista-projetos.md`,
`specs/melhoria-visual-tela-projeto.md` (reorganização anterior, fase 2.1) e `specs/css-por-area.md` (CSS por área).
Esta spec **não altera funcionalidades, rotas nem regras de negócio**.

---

## 1. Contexto

A tela do Projeto (`GET /projetos/{id}`) é o centro do sistema: mostra os dados do projeto e os 25 documentos da prestação de contas,
com as ações de cada um. Foi reorganizada na fase 2.1, mas ainda tem a "cara antiga": o usuário aprovou como padrão o estilo de Municípios
e da lista de Projetos (menu lateral, cartões arredondados, linhas largas, ações secundárias no menu ⋮), e esta tela ainda não o segue.

Hoje: cabeçalho com título e botões; aviso do ZIP em texto solto; cartão "Dados do projeto" em grade de colunas (ocupa muita altura antes dos documentos);
**tabela de 4 colunas** com as 25 linhas (Nº, Documento, Situação, Ações), com ações como links soltos, botões e painéis "Enviar arquivo" dentro da própria linha;
"Zona de perigo" no fim. Em tela estreita, cada linha vira um cartão.

Escolhas do usuário nesta descoberta (2026-11-13, por esboço): **(1)** lista de documentos em **linhas largas com ação principal** e secundárias no menu ⋮;
**(2)** topo com **cabeçalho de identidade + dados em faixa compacta**.
Decisão anterior que continua valendo: **sem resumo "N de 25" e sem barra de andamento** (DEC-P1).

## 2. Objetivo

Redesenhar a tela do Projeto no padrão visual de Municípios e da lista de Projetos, de modo que a pessoa veja **qual é o projeto, o que falta e a ação principal de cada documento**,
com os dados do projeto ocupando pouca altura e os documentos logo à vista — **sem alterar nenhum comportamento**.

## 3. Usuários envolvidos

Um único usuário (quem prepara a prestação de contas).

## 4. Requisitos funcionais (proposta visual)

**Topo**
- RF-T01 **Cabeçalho de identidade:** ícone do projeto (o mesmo da lista de Projetos), objeto como título (`h1`), subtítulo "município - UF · Projeto #N" e as ações **Editar dados** (secundária) e **Baixar prestação (ZIP)** (principal), como hoje. Trilha de navegação e `<title>` preservados.
- RF-T02 **Aviso do ZIP integrado ao cabeçalho:** mesmo conteúdo e etiquetas de hoje (quantos documentos entram, desatualizados, não gerados), logo abaixo do título; a confirmação do download com pendências é preservada.
- RF-T03 **Dados do projeto em faixa compacta:** Concedente, Processo, PP, NE, NL, Local e data, Contador (com CRC) e Prefeito como pares rótulo/valor que **quebram em linhas conforme a largura**, em cartão único de pouca altura; campo vazio mostra "—". Continua uma lista de definição (`dt`/`dd`).

**Documentos**
- RF-T04 **Linhas largas:** cada um dos 25 documentos é uma linha larga em cartão, na ordem do catálogo, com número, nome, tipo ("Junção de PDFs", "Formulário no sistema", "Upload de arquivo"…), **situação** (etiqueta) e detalhe (ex.: "16 lançamento(s)", nome do arquivo) à esquerda e as ações à direita. Textos atuais preservados.
- RF-T05 **Uma ação principal por linha:**
  - upload sem arquivo → **Enviar arquivo**;
  - upload com arquivo → **Ver**;
  - documento com formulário no sistema → o botão do formulário (ex.: "Abrir balancete");
  - documento de junção → **Gerenciar arquivos**.
- RF-T06 **Ações secundárias no menu ⋮** (só as que se aplicam à linha; sem ações secundárias, sem ⋮): **Baixar**, **Substituir** e **Remover** (destrutiva, com a confirmação atual "Remover este arquivo?").
- RF-T07 **Envio de arquivo recolhido:** nenhum campo de arquivo visível ao carregar a tela; o painel de envio (campo de arquivo + botão + dica de conversão) só aparece ao acionar "Enviar arquivo" ou "Substituir". O contrato dos formulários de upload (rota, `enctype`, `accept`, `aria-label`, `name="arquivo"`, CSRF) não muda.
- RF-T08 **Desatualizado:** documentos desatualizados mantêm a etiqueta "Desatualizado" e ganham destaque visual na linha.
- RF-T09 **Âncoras:** `#doc-<código>` continua funcionando (voltar de uma ação leva à linha certa, sem ficar sob a mensagem fixa).
- RF-T10 **Zona de perigo:** "Excluir projeto" continua só no fim da página, mesma rota, mesma confirmação.

**Geral**
- RF-T11 **Tela estreita (até 640 px):** a linha empilha (identidade e situação em cima, ações embaixo); sem rolagem horizontal da lista.
- RF-T12 **CSS:** classes exclusivas desta tela vão para `public/assets/projetos.css`; regras antigas da tabela de documentos (seção 8 do `app.css`) que ficarem sem uso são removidas; a base não cresce.

## 5. Requisitos não funcionais

- Acessibilidade: contraste mínimo (4,5:1 texto; 3:1 bordas/foco), foco visível, o ⋮ com `aria-label` por documento, a lista como lista semântica (`ul`/`li`) com nomes acessíveis; a situação não depende só de cor.
- Segurança: texto dinâmico com `e()`, todo POST com CSRF, downloads só por id (como hoje).
- Compatibilidade: Linux e Windows, sem dependências novas, sem JavaScript obrigatório (os painéis usam `details` nativo, como hoje).
- Desempenho: sem requisições extras.

## 6. Regras de negócio

Nenhuma regra muda. Em particular: nenhum documento é obrigatório; o ZIP, a junção, as conversões, a geração dos PDFs e a invalidação de "desatualizado" são as de hoje.

## 7. Fluxos de utilização

### Fluxo principal

1. O usuário abre o projeto e vê o cabeçalho, os dados em faixa e, logo abaixo, a lista de documentos.
2. Para cada linha usa a ação principal (enviar, ver, abrir formulário ou gerenciar arquivos).
3. Para baixar, substituir ou remover, abre o menu ⋮ da linha.

### Fluxos alternativos

1. Baixar o ZIP com pendências: aviso e confirmação, como hoje.
2. Voltar de uma ação por âncora (`#doc-18`): a página abre na linha do documento.
3. Tela estreita: a linha empilha e continua com as mesmas ações.

## 8. Exceções e erros

- EX-01: Projeto inexistente → página de erro 404 (como hoje).
- EX-02: Documento sem ações secundárias → sem menu ⋮ (não aparece botão vazio).
- EX-03: Campos do projeto vazios → "—" (sem quebrar a faixa).

## 9. Critérios de aceitação

- [x] CA-T01: Topo com identidade, ações, aviso do ZIP e faixa de dados compacta; todos os campos de hoje aparecem (ou "—").
- [x] CA-T02: As 25 linhas aparecem na ordem do catálogo, com número, nome, tipo, situação e detalhe; ids `doc-<código>` preservados.
- [x] CA-T03: Cada linha tem no máximo uma ação principal em destaque; as secundárias ficam no ⋮ (Baixar, Substituir, Remover) e só quando se aplicam.
- [x] CA-T04: Nenhum campo de arquivo visível ao carregar (13 campos no HTML, todos em painéis recolhidos); contrato dos formulários de upload inalterado (inclui dica de conversão do doc. 16 e 08).
- [x] CA-T05: Linha desatualizada com etiqueta e destaque; "Excluir projeto" só na zona de perigo; confirmações preservadas.
- [x] CA-T06: Tela estreita (360 px) sem rolagem horizontal; linha empilhada.
- [x] CA-T07: Contraste e foco conferidos; `aria-label` do ⋮ por documento.
- [x] CA-T08: CSS: classes novas em `projetos.css` (≤ 16 KB), regras antigas sem uso removidas da base, testes de design passando.
- [x] CA-T09: Todas as ações continuam funcionando no navegador (enviar, substituir, ver, baixar, remover, gerenciar, abrir formulário, ZIP, excluir) e a suíte de testes passa.
- [x] CA-T10: Conferência visual no navegador (largura de desktop e de celular) aprovada pelo usuário (dada como aprovada quando ele pediu o commit, 2026-11-13).

## 10. Restrições técnicas

- Mantém o `DocumentoLinhaPresenter` (situação e ações por linha) e as rotas; muda a marcação do `templates/projetos/show.php` e o CSS.
- A marcação passa de tabela (`<table>`/`<tr id="doc-…">`/`role="columnheader"`) para **lista**: os testes de `TelaProjetoTest` que dependem de `<tr>`, `role` e do nº de colunas **serão reescritos** para a nova estrutura, preservando o que importa (ids `doc-`, contratos de upload, uma ação em destaque por linha, exclusão só na zona de perigo, CSRF, escape de HTML, "Desatualizado").
- Limites de CSS (`specs/css-por-area.md`): base ≤ 40 KB, `projetos.css` ≤ 16 KB.

## 11. Fora do escopo

- Resumo "N de 25", barra de andamento ou percentuais (DEC-P1).
- Agrupar os documentos em categorias ou reordenar o catálogo.
- Mudanças nas telas dos documentos (balancete, conciliação, físico-financeiro, relação de bens, junção) e nos formulários.
- Mudanças de comportamento, rotas, banco, PDFs ou ZIP.
- Menu lateral e demais telas já redesenhadas.

## 12. Decisões pendentes

Aprovadas em 2026-11-13 (com as recomendações):

- [x] DEC-T1: Identidade do projeto no cabeçalho = **ícone de pasta** (o mesmo da lista de Projetos), por consistência. Alternativa: iniciais do objeto (ex.: "CB").
- [x] DEC-T2: "Substituir" no ⋮ **abre o mesmo painel de envio** da linha (não um segundo diálogo).
- [x] DEC-T3: Linha desatualizada = etiqueta "Desatualizado" + **borda/fundo de aviso** na linha (sem outro texto novo).
- [x] DEC-T4: Lista **semântica** (`ul`/`li`), substituindo a tabela com `role`s; testes antigos reescritos (§10).
- [x] DEC-T5: As seções 8 e 9 do `app.css` **continuam na base** se compartilhadas por outras telas; só o que for exclusivo desta tela migra para `projetos.css` (DEC-02 de `css-por-area`).

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação inicial (descoberta por esboços: linhas largas com ação principal + menu ⋮; cabeçalho de identidade com dados em faixa compacta) | 2026-11-13 |
| 1.1 | Aprovada com as recomendações; implementada e validada. Ajustes na implementação: linha desatualizada com barra e borda de aviso (sem fundo amarelo, que escondia a etiqueta); coluna de ações com largura fixa (alinhamento); `app.js` fecha menus/painéis ao abrir outro, clicar fora ou Esc | 2026-11-13 |
