# PLAN — Melhoria visual: Tela do Projeto (Fase 2.1)

**Versão:** 1.0
**Status:** Implementado (2026-11-12) — aguardando conferência visual do usuário
**SPEC relacionada:** `specs/melhoria-visual-tela-projeto.md` (v1.2, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

A tela `GET /projetos/{id}` será reorganizada **só com HTML e CSS**, em quatro etapas pequenas:

1. Tirar do template a lógica de "situação e ação de cada documento" e levá-la para um **apresentador**
   em PHP, testado. Nesta etapa o HTML gerado fica **byte a byte igual** ao atual (refatoração segura).
2. Reescrever o template com a nova estrutura: trilha, título com o objeto, cartão de dados, tabela de
   4 colunas, envio recolhido em `<details>` e zona de perigo.
3. Acrescentar ao `app.css` uma seção própria da tela, com a versão em cartões para telas estreitas (≤ 640 px).
4. Validar: testes, critérios de aceitação e conferência visual **no seu navegador**.

Decisões já tomadas e respeitadas: **sem resumo "N de 25"** (DEC-P1) e **textos atuais mantidos**:
"Disponível em etapa futura" e "O formulário da relação de bens será habilitado na próxima etapa" (DEC-P3).

## 2. Análise da arquitetura atual

- `ProjetoController::mostrar()` carrega o projeto, o catálogo (25 documentos), os documentos enviados,
  as contagens de arquivos de junção e os dados dos 3 formulários prontos (`relatorios`), e renderiza
  `templates/projetos/show.php` dentro de `templates/layout.php`.
- `show.php` (116 linhas) **decide sozinho**, linha a linha, a situação (Vazio, Enviado, Convertido, Gerado,
  Não gerado, Desatualizado), o detalhe (arquivo · tamanho, "N arquivo(s)", "N lançamento(s)") e as ações.
  Essa decisão aparece repetida em dois blocos quase iguais (junção e formulário).
- O CSS já tem o que vamos reaproveitar: `.migalhas` (trilha), `.cartao`, `.dados`, `.barra`, `.btn`,
  `button.link`, `.tag`, `.envio`, `.arq`, `.breve` e `.so-leitor`.
- As mensagens (`.alertas`) ficam **fixas no topo** ao rolar (`position: sticky`). É por isso que a linha
  `#doc-XX` pode ficar escondida atrás delas.
- Proteções automáticas que já existem e vão nos ajudar:
  - `DesignSystemTest` falha se um template usar uma classe que não existe no CSS, se houver `var(--x)` sem
    definição, se o CSS passar de 30 KB (hoje são 21,4 KB) ou se houver `!important` fora do lugar.
  - Os testes de fluxo leem o HTML da tela; a lista está na seção 10 da spec.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `src/Presenters/DocumentoLinhaPresenter.php` | **Criar** | Calcula, para cada um dos 25 documentos, a situação, o detalhe, o tipo e as ações/URLs. Tira a lógica do template e a deixa testável. |
| `src/Controllers/ProjetoController.php` | Alterar (só `mostrar()`) | Chama o apresentador e passa `linhas` ao template. Nenhuma consulta nova. |
| `templates/projetos/show.php` | Alterar (reescrever) | Nova estrutura (RF-P01 a P03, P05 a P11). |
| `public/assets/app.css` | Alterar | Nova seção **"8. TELA DO PROJETO"** (estimativa: +2,5 KB) e regras dentro do bloco responsivo ≤ 640 px. Nada do que existe é alterado. |
| `tests/DocumentoLinhaPresenterTest.php` | **Criar** | Testes de unidade: cada situação × cada tipo de documento. |
| `tests/TelaProjetoTest.php` | **Criar** | Testes que leem o HTML da tela e verificam os critérios de aceitação. |
| `specs/melhoria-visual-tela-projeto.md`, `docs/HISTORICO.md` | Alterar (no fim) | Marcar os critérios cumpridos e registrar a sessão. |

**Não serão tocados:** rotas, banco, serviços, repositórios, `templates/pdf/`, `layout.php`, JavaScript e os
testes existentes.

## 4. Banco de dados

**Nenhuma alteração.** Sem tabelas, colunas, índices ou migrações novas, e sem consultas novas: o apresentador
usa só os dados que o controlador já carrega hoje.

## 5. Serviços e regras de negócio

Nenhuma regra de negócio muda. Fica assim a divisão de responsabilidades:

| Responsabilidade | Onde |
|---|---|
| Buscar dados (projeto, enviados, contagens, formulários) | `ProjetoController::mostrar()` (como hoje) |
| Decidir situação, detalhe, tipo e ação de cada linha | **`DocumentoLinhaPresenter`** (novo; mesma lógica de hoje, só que num lugar só) |
| Desenhar a tela | `show.php` (sem lógica de decisão, só laços e condições simples) |
| Aparência | `app.css` |

**Saída do apresentador** (um item por documento, na ordem do catálogo):

```
codigo, nome, tipo            ex.: '18', 'Parecer do Controle Interno', 'Upload de arquivo'
situacao  => [texto, classe]  ex.: ['Enviado', 'ok']   (classe ∈ vazio | ok | aviso-tag)
detalhe   => ?string          ex.: 'parecer.pdf · 2 KB', '4 arquivo(s)', '3 lançamento(s)'
desatualizado => bool         para a marca lateral âmbar (RF-P07)
acao      => upload | juncao | formulario | indisponivel
temArquivo => bool
urls      => base, ver, baixar, enviar, remover, abrir (+ rotulo do formulário)
envio     => ext, accept, rotuloCampo, dicaConversao (só para upload/conversão)
```

As regras que o apresentador repete **exatamente** (RN-P02):

| Tipo | Situação |
|---|---|
| Junção | Desatualizado → Gerado → "Não gerado" (há arquivos, mas sem PDF) → Vazio |
| Formulário pronto (01, 15, 22) | Desatualizado → Gerado → "Não gerado" (há lançamentos/linhas/notas) → Vazio |
| Upload / conversão | Enviado ou Convertido (com arquivo · tamanho) → Vazio |
| Formulário não pronto (09) | Vazio, com o texto "Disponível em etapa futura" |

## 6. Fluxo técnico

```
Navegador ── GET /projetos/{id} ──► ProjetoController::mostrar()
                                    ├─ repositórios (as mesmas 6 consultas de hoje)
                                    ├─ DocumentoLinhaPresenter::linhas(...)  → 25 linhas prontas
                                    └─ View::render('projetos/show', [..., 'linhas' => ...])
show.php ─► trilha · título (objeto) · cartão de dados · tabela (25 linhas) · zona de perigo
```

**Estrutura de uma linha de upload com arquivo** (as outras seguem a mesma ideia):

```html
<tr id="doc-18" class="doc-linha">
  <td data-rotulo="Nº">18</td>
  <td data-rotulo="Documento">Parecer do Controle Interno <small class="doc-tipo">Upload de arquivo</small></td>
  <td data-rotulo="Situação"><span class="tag ok">Enviado</span> <small class="arq">parecer.pdf · 2 KB</small></td>
  <td data-rotulo="Ações"><div class="acoes">
      <a href="…/baixar?ver=1" target="_blank" rel="noopener">Ver</a>  <a href="…/baixar">Baixar</a>
      <details class="recolhe">
        <summary class="btn secundario pequeno">Substituir</summary>
        <form … class="envio"> (o MESMO formulário de hoje: campo "arquivo", CSRF, accept, required) </form>
      </details>
      <form … /remover onsubmit="return confirm('Remover este arquivo?')"> <button class="link perigo">Remover</button></form>
  </div></td>
</tr>
```

Os fluxos de envio, substituição e remoção continuam indo para as **mesmas rotas**, com os mesmos campos,
o mesmo CSRF e o mesmo redirecionamento para `#doc-XX`.

**Detalhes de cada parte da tela:**

- **Cabeçalho (RF-P01):** `<nav aria-label="Trilha">` com `ol.migalhas` (Projetos › Projeto #N, com o último
  item em `aria-current="page"`); `div.barra` com `<h1>` = objeto e `p.projeto-sub` = "Município - UF ·
  Projeto #N"; "Editar dados" como `btn secundario`. A aba continua "Projeto #N — Prestação de Contas"
  (o controlador não muda `titulo`).
- **Dados (RF-P03):** `section.cartao` com `<h2>Dados do projeto</h2>` e `dl.dados.dados--projeto`: Concedente,
  Processo, PP, NE, NL, Local e data, Contador (nome e CRC), Prefeito. Campos vazios aparecem como "—". Em telas
  largas (≥ 900 px) a grade usa 4 trilhas (rótulo, valor, rótulo, valor); abaixo disso, as 2 de hoje; e ≤ 640 px,
  1 coluna, como já acontece.
- **Lista (RF-P05 a P08):** `<h2>`, `p.aviso` com o texto atual (DEC-P3) e `table.docs` com 4 colunas. A linha
  "Desatualizado" ganha a classe `doc-pendente`, que desenha uma faixa âmbar à esquerda; a etiqueta com texto
  continua lá.
- **Zona de perigo (RF-P02):** `section.zona-perigo` no fim da página, com o aviso e o **mesmo** formulário
  `POST /projetos/{id}/excluir` + `confirm()`; o texto do botão passa a ser "Excluir projeto".
- **Tela estreita (RF-P09):** dentro de `@media (max-width: 640px)`: o cabeçalho da tabela fica oculto só
  visualmente (continua para leitores de tela), cada `tr` vira um cartão (`display: grid`) e cada célula mostra
  o seu rótulo via `td::before { content: attr(data-rotulo) }`. Para que o leitor de tela continue
  anunciando "tabela, linha, célula" mesmo com o `display` trocado, a tabela recebe os papéis ARIA
  explícitos (`role="table"`, `row`, `cell` etc.). Essa é a técnica recomendada porque alguns navegadores
  perdem a semântica de tabela quando o `display` muda. A regra `.rolagem > table { min-width: 640px }`,
  que já existe, é anulada só para `table.docs`, para não haver rolagem horizontal.
- **Âncoras (RF-P10):** `scroll-margin-top` nas linhas `.doc-linha`, com espaço suficiente para uma mensagem
  fixa (≈ 6 rem).
- **`<details>` (RF-P06):** o `summary` usa o visual de botão (`btn secundario pequeno`), sem o triângulo
  padrão e com "▾" desenhado pelo CSS. Funciona sem JavaScript e pelo teclado (Enter/Espaço).

## 7. Segurança e permissões

- Todo texto dinâmico continua passando por `e()`, **inclusive o objeto, que agora vai para o `<h1>`**.
  Haverá teste com `<script>` no objeto.
- As URLs são montadas pelo apresentador só com o id inteiro (`(int)`) e o código do catálogo, como hoje, e
  escapadas no template.
- Ficam preservados o CSRF de todos os POST, as confirmações (`confirm()`) de "Remover" e "Excluir" e o
  `rel="noopener"` do "Ver".
- O apresentador não recebe entrada do usuário além do que já vem do banco e não acessa disco nem rede.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Apresentador calcular uma situação diferente da atual | Etiqueta errada numa linha (regressão silenciosa) | Etapa 1 gera o **mesmo HTML byte a byte** que hoje (comparação automática de um projeto com todos os estados) antes de mudar o visual; testes de unidade por estado. |
| Testes atuais acoplados ao HTML quebrarem | CA-P07 | Lista da seção 10 da spec conferida: as classes `tag vazio`/`tag ok` são emitidas literalmente; nenhum teste procura `<h1>Projeto #`, "Como é preenchido" ou "Excluir". Nenhum teste existente será alterado. |
| Leitores de tela perderem a semântica de tabela no modo cartão | Acessibilidade | Papéis ARIA explícitos na tabela (técnica recomendada); cabeçalho oculto só visualmente. |
| `scroll-margin-top` em linha de tabela variar entre navegadores | Linha ficar sob a mensagem (CA-P09) | Conferência no seu navegador. Se falhar: plano B é `scroll-padding-top` só nesta página, via `html:has(.docs)`, sem afetar outras telas. |
| Visual do `<summary>` como botão variar entre navegadores | Aparência | CSS remove o marcador padrão (`list-style: none` e `::-webkit-details-marker`); conferência no navegador. |
| Não consigo tirar captura de tela no meu ambiente (limitação já conhecida) | Defeito de espaçamento passar despercebido | Checkpoint visual com você na Etapa 4 (desktop e 360 px), com um roteiro curto do que olhar. |
| Título longo (objeto extenso) | Quebra o layout (EX-P03) | `overflow-wrap: anywhere` no `<h1>`; teste com objeto longo. |

**Impacto em outras telas:** nenhum. As classes novas são exclusivas desta tela e nenhuma regra existente do CSS
é alterada.

## 9. Estratégia de implementação

Cada etapa termina com `composer test` verde e um checkpoint com você.

- [x] **Etapa 1: apresentador, sem mudança visual.** *(HTML comparado: idêntico, ignorando espaços)* Criar `DocumentoLinhaPresenter` e os testes de unidade;
      o controlador passa `linhas` e o `show.php` atual passa a usá-las **sem mudar o HTML**. Prova: salvar o HTML
      da tela antes, com um projeto de teste com todos os estados, e comparar depois (idêntico).
- [x] **Etapa 2: nova estrutura HTML.** Reescrever `show.php` (trilha, título, cartão, 4 colunas, `<details>`,
      zona de perigo, `data-rotulo`, papéis ARIA) e criar `TelaProjetoTest`. O CSS mínimo das classes novas entra
      junto, porque o `DesignSystemTest` exige que toda classe usada esteja definida.
- [x] **Etapa 3: CSS completo.** Seção "8. TELA DO PROJETO": cartão de dados em grade, `.doc-tipo`, faixa
      `.doc-pendente`, `summary` como botão, zona de perigo, `scroll-margin-top` e o modo cartões em ≤ 640 px.
- [~] **Etapa 4: validação.** *(automática feita; falta a conferência visual)* Testes, critérios CA-P01 a CA-P09, HTML bem formado no servidor real, tamanho da
      página, **conferência visual sua** (desktop, 360 px, teclado), relatório final e atualização da spec e do
      `HISTORICO.md`.

## 10. Testes e validações

**Novos: `DocumentoLinhaPresenterTest`** (unidade, ≈ 12 testes)
- [ ] Upload: vazio, enviado (detalhe com nome · tamanho); conversão: "Convertido" e a dica "Envie PNG/PPTX".
- [ ] Junção: vazio, "Não gerado" com arquivos, Gerado, Desatualizado (`desatualizado = true`).
- [ ] Formulário pronto: vazio, "Não gerado" com lançamentos (unidade correta), Gerado, Desatualizado.
- [ ] Formulário não pronto (09): ação `indisponivel`.
- [ ] 25 linhas na ordem do catálogo; `accept` correto por extensão (pdf, png, pptx).

**Novos: `TelaProjetoTest`** (lê o HTML da tela, ≈ 10 testes)
- [ ] CA-P01: todo `<input type="file">` está dentro de um `<details>`; nenhum fica fora.
- [ ] CA-P02: em cada linha, no máximo 1 elemento com classe `btn` fora do conteúdo do `<details>`.
- [ ] CA-P03: `<h1>` contém o objeto (escapado); subtítulo com município e "Projeto #N"; `<title>` inalterado;
      o formulário `/excluir` aparece **uma vez**, dentro da zona de perigo, com o `confirm()`.
- [ ] CA-P05: 25 linhas `id="doc-XX"` na ordem; textos DEC-P3 presentes; "Como é preenchido" ausente.
- [ ] Segurança: `<script>` no objeto sai escapado; todo formulário POST tem `_csrf`.
- [ ] Os campos de arquivo mantêm `name="arquivo"`, `required`, `accept` e `aria-label`.

**Regressão**
- [ ] Os 247 testes atuais passam **sem nenhuma alteração** (CA-P07).
- [ ] Fluxos reais no servidor (CA-P06): enviar, substituir, remover, ver, baixar, gerenciar arquivos, abrir os 3
      formulários, editar dados, excluir projeto.

**Manuais (no seu navegador)**
- [ ] CA-P04: largura de 360 px sem rolagem horizontal (pelas ferramentas do navegador, F12 → modo dispositivo).
- [ ] CA-P08: navegação com Tab, foco visível, `<details>` abrindo com Enter/Espaço.
- [ ] CA-P09: após enviar um arquivo, a linha fica visível abaixo da mensagem.

## 11. Critérios de conclusão

- CA-P01 a CA-P09 da spec verificados (automáticos e manuais) e marcados.
- `composer test` verde: 247 testes atuais + os novos.
- Nenhuma alteração fora dos arquivos da seção 3.
- Spec e `docs/HISTORICO.md` atualizados; pendências registradas.

## 12. Decisões técnicas pendentes

- [x] DT-1 **Decidido: criar o apresentador.** **Criar o apresentador** (`src/Presenters/`, uma pasta nova). A spec deixou isso "se aprovado".
      *Recomendo:* tira a lógica duplicada do template, permite testar cada estado isoladamente e servirá
      de modelo para as próximas telas. *Alternativa:* manter a lógica no template, que fica mais simples
      de entender no início, mas com mais risco e sem testes de unidade.
- [x] DT-2 **Decidido: "Excluir projeto".** **Texto do botão da zona de perigo:** "Excluir projeto" (hoje é "Excluir"), conforme o esboço da
      spec. A confirmação e a rota continuam iguais.

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12; decisões DEC-P1/P3 registradas na v1.2).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12; usuário pediu para seguir direto, conferência visual ao final).
