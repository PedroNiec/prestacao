# PLAN — Melhoria visual: demais telas (Fase 2.2)

**Versão:** 1.0
**Status:** Implementado (2026-11-12) — aguardando conferência visual do usuário
**SPEC relacionada:** `specs/melhoria-visual-demais-telas.md` (v1.1, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Só templates e CSS. Primeiro crio **2 pedaços reaproveitáveis** de template (trilha e cartão do PDF) e os
componentes de CSS que faltam. Depois aplico o padrão **tela por tela**, em 6 etapas, com os testes rodando ao fim
de cada uma. Nenhum controlador, serviço, rota ou banco é alterado.

## 2. Análise da arquitetura atual

- Todas as telas de documento já recebem `$projeto`, que inclui `objeto`, `municipio_nome` e `municipio_uf`. A
  trilha não precisa de nenhuma consulta nova.
- `View::render($nome, $dados, false)` já renderiza um template sem o layout. Vou usar isso para os pedaços
  reaproveitáveis (`templates/partes/`), sem criar mecanismo novo.
- O CSS já tem `.migalhas`, `.cartao`, `.kpis/.kpi`, `.estado-vazio`, `.botoes`, `.dica`, `.so-leitor` e os
  componentes da tela do Projeto (`.recolhe`, `.zona-perigo` etc.).
- Os scripts dependem de `id`s e atributos: `#form-projeto`, `#municipio_id`, `#local`, `#prefeito_nome`,
  `#contador_nome`, `#contador_crc` (`projeto-form.js`); `#form-lancamento`, `#origem`, `#fonte`
  (`lancamento-form.js`); `#lista`, `data-arrastavel`, `#form-ordem`, `draggable`, `data-id` (`juncao.js`). **Todos
  ficam iguais.**
- O `DesignSystemTest` exige que toda classe usada nos templates exista no CSS (os templates de `partes/` entram
  nessa verificação).

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `templates/partes/trilha.php` | **Criar** | Trilha única (RF-T01): recebe uma lista de itens `[rótulo, url?]`; objeto com `title` para o texto inteiro. |
| `templates/partes/pdf.php` | **Criar** | Cartão "PDF do documento" (RF-T03), hoje repetido em 4 telas com pequenas diferenças. Cada tela passa os seus textos atuais. |
| `templates/projetos/index.php`, `projetos/form.php` | Alterar | RF-T10, RF-T12 |
| `templates/municipios/index.php`, `municipios/form.php` | Alterar | RF-T11, RF-T12 |
| `templates/balancete/index.php`, `balancete/form.php` | Alterar | RF-T13, RF-T14 |
| `templates/fisico/index.php`, `fisico/form.php` | Alterar | RF-T15, RF-T16 |
| `templates/conciliacao/index.php`, `conciliacao/nota_form.php` | Alterar | RF-T17, RF-T18 |
| `templates/documentos/juncao.php` | Alterar | RF-T19 |
| `templates/erro.php` | **Não muda** | DEC-T4 = manter |
| `public/assets/app.css` | Alterar | Nova seção "9. DEMAIS TELAS": trilha com objeto, cartão do PDF, lista em cartões, rodapé de formulário, ações de linha, classes que substituem os 9 `style=""`. |
| `tests/DesignSystemTest.php` | Alterar **só se preciso** | DEC-T5: limite do CSS de 30 para 40 KB, apenas se passar de 30 KB. |
| `tests/TelasPadraoTest.php` | **Criar** | Verifica os critérios CA-T01 a CA-T05 lendo o HTML de cada tela. |
| `specs/…`, `plans/…`, `docs/HISTORICO.md` | Alterar (no fim) | Registro. |

## 4. Banco de dados

**Nenhuma alteração.**

## 5. Serviços e regras de negócio

Nenhuma alteração. Os indicadores (KPIs) usam os totais que as telas já recebem: `$totais['receita']`,
`['despesa']` e `['saldo']` no balancete; `$totais['receitas']`, `['despesas']`, `['saldo']`, `['bancario']` e
`['conciliacao']` na conciliação. Nenhum cálculo novo.

## 6. Fluxo técnico (como cada tela fica)

**Trilha:** em cada tela, uma chamada `View::render('partes/trilha', ['itens' => [...]], false)`. Exemplo (balancete):
`[['Projetos', '/projetos'], [objeto, '/projetos/1#doc-01'], ['01 Balancete', null]]`. A trilha é um
`<nav aria-label="Trilha de navegação"><ol class="migalhas">`. O item do objeto usa a classe `trilha-objeto`
(cortado com "…" pelo CSS, com `title` mostrando o texto inteiro). Projeto sem objeto → "Projeto #N".

**Cartão do PDF:** `View::render('partes/pdf', [...], false)` com: situação (sem PDF, desatualizado ou gerado),
os textos da tela (por exemplo, "PDF não gerado" / "Os lançamentos mudaram desde a última geração."), a URL de
geração, as URLs de Ver e Baixar e se o botão fica desabilitado (junção sem arquivos). As etiquetas e os textos
continuam **exatamente** os de hoje em cada tela.

**Por tela:**

| Tela | O que muda |
|---|---|
| Lista de projetos | Lista `ul.lista-cartoes`: objeto como link (`<a>` para `/projetos/N`), linha secundária "Município - UF · Processo · Projeto #N". Vazia → `estado-vazio` com botão. O texto "Projetos" e o botão "Novo projeto" ficam. |
| Lista de municípios | Mesmo componente: nome como título, prefeito e contador como linha secundária, nº de projetos como etiqueta neutra (`tag info`, "N projeto(s)"), Editar · Excluir discretos. |
| Formulários (projeto, município, lançamento, meta, nota) | Trilha; `form.form` passa a ter cabeçalho e seções; rodapé `.botoes` com Salvar (`btn`) e Cancelar (`btn secundario`). Campos, `name`, `id`, `required`, `maxlength` e `value` **idênticos**. O `style="text-transform:uppercase"` da UF vira classe. |
| Balancete | Trilha; cabeçalho (h1 "01 — Balancete", subtítulo com objeto e município, botão "Novo lançamento"); cartão do PDF; `.kpis` com 3 indicadores; tabela atual; "Totais por origem" num cartão. Vazio → `estado-vazio` com o texto atual. |
| Físico-financeiro | Trilha; cabeçalho; cartão do PDF; seções "Físico" e "Financeiro" (tabelas atuais). Vazio → `estado-vazio`. |
| Conciliação | Trilha; cabeçalho com atalhos (`#receitas`, `#notas`, `#dados`); cartão do PDF; `.kpis` com 5 indicadores. O de Conciliação usa `kpi ok` + "fechou" ou `kpi erro` + "não fechou". O aviso amarelo atual fica abaixo, igual. As 3 seções em cartões; os 3 `style=""` viram classes. |
| Junção | Trilha; cabeçalho; cartão do PDF (botão desabilitado sem arquivos, como hoje); lista atual; "Adicionar PDFs" num cartão abaixo da lista. O `style=""` vira classe. |

**Ações de linha (RF-T06):** a célula `td.acoes` das tabelas (balancete, físico-financeiro, notas) mantém a estrutura e
os formulários. O CSS agrupa as setas (↑ ↓) e dá o mesmo espaçamento; Excluir já é `link perigo`.

**Celular:** `.lista-cartoes` já é naturalmente empilhada; as tabelas numéricas continuam em `.rolagem`.

## 7. Segurança e permissões

- Os pedaços de template recebem só valores já escapados com `e()` no próprio pedaço (o objeto vai para `title`
  e para o texto, sempre escapado). Haverá teste com `<script>` no objeto em uma tela interna.
- CSRF, `confirm()`, `rel="noopener"` e `target="_blank"` preservados.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Quebrar um script ao mexer no HTML (`juncao.js`, `projeto-form.js`, `lancamento-form.js`) | Arrastar, preenchimento automático ou fonte automática param de funcionar | `id`s e atributos listados na §2 ficam iguais; teste novo confere a presença de cada um. |
| Teste existente acoplado a texto | Falha nova (CA-T08) | Textos da §10 da spec preservados; suíte completa ao fim de cada etapa, comparada com a lista de falhas de ambiente. |
| CSS passar de 30 KB | Teste de tamanho falha | Reaproveitar componentes; se passar, aplicar DEC-T5 (40 KB) e registrar. |
| Remover `style=""` mudar espaçamentos | Detalhe visual | Cada `style` vira uma classe com o mesmo valor. |
| Trilha com objeto muito longo | Quebra de layout | `max-width` + `text-overflow: ellipsis` no item do objeto; teste com objeto longo. |
| Sem captura de tela no meu ambiente | Detalhe visual passar despercebido | Conferência sua ao final (servidor de demonstração na porta 8010, com dados de exemplo em todas as telas). |

## 9. Estratégia de implementação

Cada etapa termina com a suíte completa rodando, sem nenhuma falha nova.

- [x] **Etapa 1: base.** `partes/trilha.php`, `partes/pdf.php` e a seção 9 do CSS; esqueleto do `TelasPadraoTest`.
- [x] **Etapa 2: projetos e municípios** (listas e formulários).
- [x] **Etapa 3: balancete** e formulário de lançamento.
- [x] **Etapa 4: físico-financeiro** e formulário de meta/etapa.
- [x] **Etapa 5: conciliação** e formulário de nota fiscal.
- [x] **Etapa 6: junção.**
- [~] **Etapa 7: validação.** *(automática feita: 16 telas sem erro de estrutura; falta a conferência visual)* HTML bem formado em todas as telas no servidor de demonstração, tamanho das páginas,
      critérios da spec, **sua conferência visual**, atualização da spec, do plano e do `HISTORICO.md`.

## 10. Testes e validações

**Novo: `TelasPadraoTest`**
- [ ] CA-T01: cada tela interna (11 URLs) tem `aria-label="Trilha de navegação"` e não tem "← Voltar".
- [ ] CA-T02: as 4 telas de documento têm o cartão do PDF, com as etiquetas atuais ("PDF não gerado", "Desatualizado",
      "PDF gerado"; na junção, "Não gerado" e "Gerado").
- [ ] CA-T03: `class="aviso"` não aparece nas listas vazias; aparece na conciliação que não fecha.
- [ ] CA-T04: nenhum `style="` nos templates de tela (varredura dos arquivos, fora `pdf/`).
- [ ] CA-T05: indicadores no balancete (3) e na conciliação (5); "fechou" quando a diferença é zero e "não fechou"
      quando não é.
- [ ] Scripts: os `id`s e atributos da §2 presentes nas telas correspondentes.
- [ ] Segurança: objeto com `<script>` sai escapado na trilha e no `title`.

**Regressão:** todos os testes atuais, **sem alteração** (exceto o limite do CSS, se a DEC-T5 for acionada).

**Manual (você, no navegador):** desktop e 360 px em todas as telas; arrastar na junção; preenchimento automático no
formulário de projeto; fonte automática no lançamento.

## 11. Critérios de conclusão

- CA-T01 a CA-T09 da spec verificados e marcados.
- Suíte completa sem nenhuma falha nova.
- Nenhum arquivo fora da §3 alterado.
- Spec, plano e `HISTORICO.md` atualizados.

## 12. Decisões técnicas pendentes

Nenhuma. As decisões de negócio e de visual foram tomadas na spec (DEC-T1 a T5).

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12; seguir direto, conferência visual ao final).
