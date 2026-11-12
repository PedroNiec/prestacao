
# PLAN — Melhoria visual da interface: Fundação visual (Fase 1)

**Versão:** 1.0
**Status:** Implementado (2026-11-12) — aguardando conferência visual do usuário
**SPEC relacionada:** specs/melhoria-visual-fundacao.md (v1.1, aprovada em 2026-11-12)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Reescrever o `app.css` de forma organizada (tokens → base → componentes → utilitários →
responsivo), **mantendo todos os nomes de classe atuais**, e adaptar o layout global
(`layout.php`) para: link "ir para o conteúdo", menu com item ativo, área de mensagens fixa no topo
com botão de fechar, favicon e página de erro no novo visual. Acrescentar um JavaScript mínimo
(`app.js`, fecha mensagens) e proteger a fundação com **testes automáticos novos** (contraste dos
tokens, tamanho, ausência de recursos externos, e "toda classe usada nos templates está definida no
CSS"). Só HTML, CSS e JS puros; nenhuma dependência.

Não há mudança em controllers, serviços, domínio, repositórios, banco, rotas de negócio ou PDFs.

## 2. Análise da arquitetura atual

- **CSS:** um único arquivo, `public/assets/app.css` (61 linhas, 35 classes), servido pelo servidor
  embutido do PHP (`composer serve`), carregado pelo `layout.php`.
- **Layout:** `templates/layout.php` (24 linhas) renderiza cabeçalho, mensagens (`Session::pullFlash()`
  → `.msg .msg-ok|.msg-erro`) e `<main class="conteudo">`. Não conhece a página atual.
- **Templates:** 14 (menos os de `pdf/`), com 9 estilos inline distribuídos em 5 arquivos.
- **JS existente (por tela, não global):** `juncao.js`, `projeto-form.js`, `lancamento-form.js`.
- **Testes acoplados ao HTML (precisam continuar passando sem alteração):**

| Teste | Dependência |
|---|---|
| `CadastrosTest:84` | exatamente 25 ocorrências de `class="tag vazio"` na tela do projeto |
| `UploadFlowTest:67` | 1 ocorrência de `class="tag ok"` |
| `ConciliacaoTest:236,245` | `class="tag aviso-tag" title="Diferente do balancete"` |
| `JuncaoTest:269` | 3 ocorrências de `draggable="true"` |
| vários | textos como "Projetos", "Desatualizado", "Não gerado", "Convertido", valores `value="1000,50"` |

  Como a fundação **não altera o markup dessas partes**, eles seguem válidos. Qualquer alteração
  de markup nesta fase se limita aos itens da seção 3.

- **Tabelas sem contêiner de rolagem** (causa de estouro em tela pequena): lista de documentos do
  projeto, lista de projetos, lista de municípios e tabela de receitas da conciliação.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `public/assets/app.css` | **Reescrever** | tokens, base, componentes, responsivo, acessibilidade |
| `public/assets/app.js` | Criar | fechar mensagens (≤ 3 KB), com degradação sem JS |
| `public/assets/favicon.svg` | Criar | elimina o 404 do ícone da aba |
| `templates/layout.php` | Alterar | skip link, menu ativo (`aria-current`), região de mensagens, favicon, `app.js` |
| `templates/erro.php` | Alterar | estado centralizado com ação de voltar (mesmos textos e status) |
| `templates/projetos/show.php` | Alterar (1 envoltório) | tabela de documentos em `.rolagem` |
| `templates/projetos/index.php` | Alterar (1 envoltório) | idem |
| `templates/municipios/index.php` | Alterar (1 envoltório) | idem |
| `templates/conciliacao/index.php` | Alterar (1 envoltório) | tabela de receitas em `.rolagem` |
| `tests/DesignSystemTest.php` | Criar | guardas automáticas (seção 10) |
| `plans/anexos/app-css-original.css` | Criar (cópia) | backup do CSS atual, já que o projeto não usa git |
| `templates/guia_visual.php` + rota `GET /guia-visual` | Criar (**opcional, DT-V1**) | página única com todos os componentes e estados, para conferência visual |

**Não serão tocados:** controllers, services, domain, repositories, migrations, `templates/pdf/*`,
JS de tela (`juncao.js` etc.) e a lógica de qualquer rota de negócio.

## 4. Banco de dados

Nenhuma alteração.

## 5. Serviços e regras de negócio

Nenhuma alteração. Responsabilidades visuais:

- **`app.css`**, em seções ordenadas e comentadas no topo (documentação dos tokens):
  1. Tokens (`:root`): cores, espaçamento (4…48), raios, sombras, tipografia, larguras.
  2. Base: reset leve, tipografia, links, foco visível, `prefers-reduced-motion`.
  3. Layout: cabeçalho, navegação, contêiner (`.conteudo`, modificador largo), rodapé, skip link.
  4. Componentes: botões, formulários, tabelas, etiquetas, alertas, cartões, estados vazios,
     migalhas, faixa de contexto, KPI, barra de página.
  5. Compatibilidade: as classes atuais (`.btn`, `.btn.secundario`, `.btn.perigo`, `button.link`,
     `.tag*`, `.form`, `.caixa`, `.dados`, `.aviso`, `.msg*`, `.rolagem`, `.num`, `.acoes`,
     `.linha`, `.botoes`, `.dica`, `.arq`, `.breve`, `.envio`, `.lista-arquivos`, `.compacta`,
     `.form-larga`, `.inline`…) mapeadas aos novos tokens.
  6. Responsivo: ≤ 1024 px e ≤ 640 px.
- **`app.js`:** delegação de evento; clique no botão `.msg-fechar` remove a mensagem; se a região
  ficar vazia, é ocultada. Sem dependências; sem efeito se desativado.
- **`layout.php`:** calcula o item ativo pelo caminho da requisição (`$_SERVER['REQUEST_URI']`,
  com valor padrão para CLI/testes); mantém `Session::pullFlash()` e o escape `e()`.

## 6. Fluxo técnico

1. Navegador pede `GET /projetos/1/balancete` → rota existente, sem mudança.
2. O template é renderizado dentro de `layout.php` (agora com skip link, menu com
   `aria-current="page"` em "Projetos", região `role="region" aria-label="Mensagens"`).
3. O navegador carrega `app.css` (estilos) e `app.js` (`defer`).
4. Após um POST com redirecionamento para uma âncora, a região de mensagens é `position: sticky`
   no topo da janela: a mensagem permanece visível mesmo com a rolagem até a âncora; o botão
   fecha a mensagem.

## 7. Segurança e permissões

- Nenhuma rota nova de negócio; CSRF, escape de saída e demais proteções inalteradas.
- Todo texto dinâmico novo no layout passa por `e()`.
- Nenhum recurso externo: o teste automático falha se houver `http(s)://` em CSS, JS ou layout
  (exceto o *namespace* `xmlns` de SVG inline, se existir).
- A página `/guia-visual` (se aprovada) é estática e sem dados; não aceita entrada nem lê o banco.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Reescrever o CSS quebrar uma tela (efeito colateral) | Tela desalinhada ou ilegível | Manter todas as classes; teste que garante que toda classe usada nos templates existe no CSS; backup do CSS original; conferência visual por tela na validação |
| Regras novas de `.form input` afetarem `radio`/`checkbox`, `file` e campos dentro de tabelas | Controles deformados | Regras específicas e testadas por seletor (`.form label.inline input`, `.envio input[type=file]`, `.form-larga input`) mantidas explicitamente |
| Mensagem fixa cobrir conteúdo | Atrapalha leitura | Altura contida, botão de fechar, fixa só dentro de `<main>`; sem JS, comportamento normal (sem sobreposição agressiva) |
| `:has()` (asterisco de obrigatório) em navegador antigo | Asterisco não aparece | Degradação silenciosa (EX-V02); não afeta o envio do formulário |
| Testes acoplados ao HTML falharem | Falsa regressão | Markup dessas partes não muda; rodar a suíte após cada etapa |
| Não conseguir ver as telas no ambiente de desenvolvimento | Defeito visual não detectado por mim | Conferência no navegador do usuário, guiada por checklist (seção 10) e, se aprovada, pela página `/guia-visual` |
| Perder o CSS original (projeto sem git) | Difícil reverter | Cópia em `plans/anexos/app-css-original.css` antes de reescrever |
| CSS/JS crescerem demais | Desempenho/manutenção | Teste automático limita CSS ≤ 30 KB e JS ≤ 3 KB |

## 9. Estratégia de implementação

Cada etapa termina com a suíte de testes verde e um checkpoint.

- [x] Etapa 1: **Preparação** — copiar o CSS atual para `plans/anexos/`; criar
      `tests/DesignSystemTest.php` com as guardas (inicialmente validando o CSS atual onde cabe).
- [x] Etapa 2: **Tokens e base** — reescrever o início do `app.css` (tokens, base, tipografia,
      foco, redução de movimento) preservando as classes existentes.
- [x] Etapa 3: **Componentes** — botões, formulários, tabelas, etiquetas, cartões, alertas, estados
      vazios e componentes novos (migalhas, contexto, KPI, barra de página), já com a camada de
      compatibilidade.
- [x] Etapa 4: **Layout global** — `layout.php` (skip link, menu ativo, mensagens fixas com fechar),
      `app.js`, `favicon.svg`, `erro.php`.
- [x] Etapa 5: **Responsividade** — pontos de quebra, quebra de linha de grupos de ações/listas,
      formulários empilhando, alvos de toque, e os 4 envoltórios `.rolagem` nos templates.
- [x] Etapa 6: **Guia visual** (somente se DT-V1 aprovada) — `GET /guia-visual`.
- [x] Etapa 7: **Validação** — suíte completa, relatório de contraste, checklist visual com o
      usuário e relatório final de pendências.

**Checkpoints para você conferir no navegador:** ao fim das etapas 4 e 5 (ou uma única vez ao fim
da 5, se preferir).

## 10. Testes e validações

**Automáticos (novo `tests/DesignSystemTest.php`):**

- [ ] Contraste: lê os tokens do `:root` do CSS e verifica texto ≥ 4,5:1 e bordas/anel ≥ 3:1 para
      todos os pares declarados na seção 6 da spec (CA-V02).
- [ ] Toda classe usada em `templates/**/*.php` (exceto `pdf/`) está definida no CSS (ou consta em
      uma lista explícita de classes só de marcação/JS) (CA-V06).
- [ ] Tamanho: CSS ≤ 30 KB e JS ≤ 3 KB (CA-V09).
- [ ] Offline: nenhum `http(s)://` externo em CSS, JS e layout.
- [ ] Layout: skip link, `lang="pt-BR"`, favicon, `aria-current` no item correto, região de
      mensagens com botão de fechar, e páginas de erro com o mesmo status de antes (CA-V07).
- [ ] `templates/pdf/*` inalterados (comparação por hash antes/depois) (CA-V08).
- [ ] **Regressão:** os 200 testes existentes passam **sem alteração** (CA-V01).

**Manuais, no seu navegador (checklist que entregarei):**

- [ ] Em cada tela (projetos, projeto, balancete, lançamento, físico-financeiro, conciliação,
      junção, municípios, formulários, erro): aparência coerente com a fundação.
- [ ] Janela estreita (≈ 360 px, pelas ferramentas do navegador): sem rolagem horizontal da página
      (CA-V03).
- [ ] Tecla Tab percorre tudo com foco visível (CA-V04).
- [ ] Enviar um arquivo / salvar um formulário: a mensagem aparece no topo e pode ser fechada
      (CA-V05).
- [ ] Favicon aparece e o console do navegador não mostra 404.

## 11. Critérios de conclusão

- CA-V01 a CA-V09 da spec atendidos (os de conferência visual confirmados por você).
- Suíte completa verde, incluindo os testes novos.
- Relatório de contraste e checklist visual registrados; pendências e limitações documentadas.
- Spec e plano atualizados com o histórico das decisões tomadas durante a implementação.

## 12. Decisões técnicas pendentes

- [x] DT-V1 (aprovada; implementada): **Página `/guia-visual`** (mostra todos os componentes e estados em um só lugar).
      *Recomendo incluir:* é a forma mais eficiente de você conferir a fundação sem percorrer 14
      telas, e pode ser removida ao fim. Acrescenta uma rota somente-leitura, fora do escopo
      original da spec; por isso depende da sua aprovação.
- [x] DT-V2 (adotada): **Ícones de alerta** como caracteres (✓ ! ✕ i) em CSS, sem SVG. *Recomendo*: mais simples
      e sem arquivos extras.
- [x] DT-V3 (adotado): **Backup do CSS original** em `plans/anexos/app-css-original.css` (arquivo extra no
      repositório; pode ser apagado depois). *Recomendo manter.*
- [ ] DT-V4: Decisões DEC-V1 e DEC-V2 da spec (itens opcionais e indicador de processamento) seguem
      **fora**, conforme o padrão, salvo nova instrução.

## 13. Aprovação

- [x] Especificação aprovada (2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12), incluindo o guia visual.
