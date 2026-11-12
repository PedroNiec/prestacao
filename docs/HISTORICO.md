# Histórico do projeto — Sistema de Prestação de Contas

**Atualizado em:** 2026-11-12
**Para quem é:** você e qualquer sessão futura do Claude que precise saber **onde paramos, o que foi
decidido e por quê**. Complementa os documentos oficiais em `specs/` e `plans/` (que são a fonte da
verdade de cada funcionalidade). Para retomar em outro computador, veja `docs/GUIA-OUTRO-COMPUTADOR.md`.

> **Mantenha este arquivo vivo.** Ao fim de cada sessão de trabalho, peça ao Claude: *"atualize
> docs/HISTORICO.md com o que fizemos hoje"*.

---

## 1. O que é o sistema

Sistema **web local** para montar a **prestação de contas de convênios com prefeituras**. A prestação é
um conjunto de **25 PDFs** (balancete, notas fiscais, comprovantes, ART, CND, conciliação bancária etc.).

| Item | Definição |
|---|---|
| Usuário | **Uma pessoa**, sem login e sem perfis |
| Execução | Local, no computador do usuário (`127.0.0.1:8000`); **não expor à internet** |
| Tecnologia | **PHP 8.3 puro** + Composer, **SQLite**, Dompdf (PDF dos relatórios), Ghostscript (junção de PDFs), LibreOffice (PowerPoint → PDF) |
| Dependências Composer | `dompdf/dompdf` e, só em desenvolvimento, `phpunit/phpunit` |
| Idioma | Interface e documentação em português do Brasil |

**Como cada documento é produzido** (todos aparecem em todo projeto; **nenhum é obrigatório**):

| Comportamento | Documentos |
|---|---|
| **Junção** de vários PDFs (enviar, reordenar, gerar) | 02, 03, 04, 05, 06, 07, 13, 14 |
| **Upload** de um PDF pronto | 10a, 10b, 11, 12, 17, 18, 19, 20, 21, 23, 24 |
| **Conversão** para PDF | 08 (PowerPoint `.pptx`), 16 (imagem `.png`) |
| **Formulário** no sistema, que gera o PDF | 01 Balancete, 09 Relação de bens, 15 Conciliação, 22 Físico-financeiro |

---

## 2. Como trabalhamos (processo)

O projeto usa **Spec-Driven Development (SDD)**, definido em `CLAUDE.md` e na skill
`.claude/skills/spec-driven/` (comando `/spec-driven`). Resumo:

1. **Descoberta** (perguntas, uma de cada vez) → 2. **Spec** em `specs/` → **aprovação** →
3. **Plano técnico** em `plans/` → **aprovação** → 4. **Autorização explícita** para implementar →
5. **Implementação em etapas pequenas**, com checkpoint → 6. **Validação** e relatório.

Regras que valem sempre: nunca implementar sem as 3 aprovações; não presumir decisões de negócio; não
adicionar dependências sem autorização; preservar o que já funciona; silêncio não é aprovação.

---

## 3. Estado atual (2026-11-12)

**Testes: 247 passando (720 asserções).** Rodar com `composer test`.

### 3.1 Sistema (plano `plans/prestacao-de-contas-plan.md`)

| Etapa | Entrega | Situação |
|---|---|---|
| 1 | Base: roteador, SQLite + migrações, layout, CSRF, README | ✔ |
| 2 | Cadastros: municípios e projetos; tela do projeto com os 25 documentos | ✔ |
| 3 | Upload seguro (substituir, remover, baixar) — 11 documentos | ✔ |
| 4 | Junção de PDFs: vários arquivos, reordenar (arrastar ou ↑↓), gerar, "desatualizado" | ✔ |
| 5 | Conversões: PNG → PDF (16) e PPTX → PDF (08) | ✔ |
| 6 | **Balancete** (01): lançamentos, totais, PDF | ✔ |
| 7 | **Físico-financeiro** (22): várias metas, valores digitados, PDF | ✔ |
| 8 | **Conciliação bancária** (15): receitas do balancete (editáveis), notas digitadas, PDF | ✔ |
| **9** | **Relação de bens** (09) | **⏳ pendente** (falta decisão — ver §9) |
| **10** | **Entrega: ZIP numerado** com todos os PDFs (RF-21) | **⏳ pendente** |
| 11 | Validação final contra os critérios de aceitação | ⏳ pendente |

> O download **individual** de cada PDF já funciona. O **ZIP** (Etapa 10) ainda **não** existe.

### 3.2 Melhoria visual da interface (iniciada depois da Etapa 8)

| Fase | Documento | Situação |
|---|---|---|
| Levantamento visual | (relatório na conversa) | ✔ concluído |
| **Fundação visual** | `specs/melhoria-visual-fundacao.md` + `plans/melhoria-visual-fundacao-plan.md` | ✔ **implementada**; você conferiu e não achou nada estranho |
| **Tela do Projeto** | `specs/melhoria-visual-tela-projeto.md` | spec **aprovada**; **plano técnico ainda não escrito** |
| Demais telas | — | uma por vez, cada uma com spec → plano → aprovação |

---

## 4. Linha do tempo resumida

1. **Descoberta:** entrevista sobre o problema (prestação de contas), usuários, formato das entradas
   e saídas, e análise da planilha `planilha/Balancete Prestação de Contas.xlsx`.
2. **Spec e plano** aprovados (spec v1.8 hoje; ver `specs/prestacao-de-contas.md`, seção 14).
3. **Etapas 1 a 8**, cada uma com testes e verificação no servidor real. Em várias delas a revisão
   (inclusive visual, renderizando os PDFs em imagem) achou defeitos que os testes não pegavam; foram
   corrigidos e passaram a ter teste de regressão.
4. **Melhoria visual:** levantamento → fundação visual (tokens de design, componentes, layout, página
   `/guia-visual`) → tela do Projeto (spec aprovada).

---

## 5. Decisões de negócio tomadas pelo usuário

| Tema | Decisão |
|---|---|
| Plataforma | Web local, PHP, SQLite, sem login, uma pessoa |
| Documentos | Todos os 25 em todo projeto; nenhum obrigatório; tipo de projeto (obra/serviço/bens) não muda nada |
| PDFs escaneados | Só anexados; **sem OCR** |
| Formulários | Balancete, físico-financeiro, conciliação e relação de bens são **preenchidos no sistema** (não mais na planilha) |
| Junção | Enviar vários, reordenar, juntar |
| Entrega | PDFs individuais **e** ZIP |
| Municípios | Cadastro reaproveitável; as assinaturas são **copiadas** para o projeto (mudar o município depois não altera projetos antigos) |
| Aparência dos PDFs | "Visual muito próximo da planilha" |
| Balancete | Receita = entrou na conta, Despesa = saiu (uma coluna cada); **origem** (Convênio/Rendimentos/Contrapartida) define a **fonte**; retenções (IRRF etc.) são **linhas de despesa separadas, sem tipo** |
| Físico-financeiro | **Várias** metas/etapas; financeiro **sempre digitado** (não deriva do balancete); "no período" e "até o período" informados separadamente |
| Conciliação | Receitas vêm do balancete e são **editáveis** (com "alterado" e "Restaurar"); notas fiscais **digitadas** na própria conciliação; cabeçalho padrão do projeto; só o Prefeito assina; **Total de despesas = soma do bruto das notas** (**devoluções ficam fora do total**) |
| Itens (relação de bens) | Calculados a partir dos dados, mas editáveis (RF-18) — detalhes pendentes |
| Visual | Começar pela fundação; incluir o guia visual; itens opcionais (contador "n de 25", filtro, modal próprio, envio automático) **fora** por padrão |

---

## 6. Premissas provisórias (assumidas por padrão; confirme quando puder)

| Código | Premissa | Onde mudar |
|---|---|---|
| DEC-03 | CNO (doc. 17) aceita **só PDF** | `DocumentCatalog::extensoesUpload()` |
| DEC-04 | Junção aceita **só PDF** (não imagens) | `JuncaoService` |
| DEC-05 | Limite de **50 MB** por arquivo; backup = copiar a pasta `storage/` | `FileStorage::MAX_BYTES`, `composer serve` |
| DEC-08 | PowerPoint aceito só **`.pptx`** (não `.ppt`) | `FileStorage::MIMES`, catálogo |
| — | Linha **"SALDO (RECEITA − DESPESA)"** acrescentada ao PDF do balancete (a planilha não tem, mas o título cita "Saldo") | `templates/pdf/balancete.php` |
| — | CNPJ validado só pela quantidade de dígitos (14), não pelos dígitos verificadores | `Validator::lancamento()` |
| — | Receita digitada fora do balancete só nos 3 slots de repasse, 3 de contrapartida e 1 de rendimentos | `ConciliacaoCalculator` |
| DEC-P1 | Tela do Projeto **sem** o resumo "N de 25 com arquivo" (recomendado incluir; decisão em aberto) | `specs/melhoria-visual-tela-projeto.md` |
| DEC-P3 | Tela do Projeto **mantém** os textos "Disponível em etapa futura" e a frase de desenvolvimento (recomendado trocar; decisão em aberto) | idem |

**Ponto que merece reconsideração:** como as devoluções ficam **fora** do total de despesas, um projeto
que devolveu saldo mostra **conciliação diferente de zero** (a tela avisa, mas o PDF sai com a diferença).
Se a planilha antiga fechava em zero nesses casos, vale mudar (é uma alteração pequena).

---

## 7. Descobertas técnicas importantes (armadilhas já resolvidas)

- **Ghostscript retorna código 0 mesmo sem conseguir ler um PDF** (por exemplo, protegido por senha): o
  PDF final sairia **sem aquelas páginas**, em silêncio. A junção agora tem 3 camadas: conta as páginas de
  cada origem, trata as mensagens de erro e confere o total de páginas do resultado. Nada é gravado se não
  conferir.
- **LibreOffice também retorna 0 quando falha** ao abrir um arquivo. A conversão valida a existência e a
  contagem de páginas do PDF gerado.
- **Dompdf** ignora `<colgroup>`; no layout fixo, as larguras vêm da **primeira linha** da tabela. Não
  suporta `−` (U+2212) com as fontes padrão (usar `-`). `page-break-inside: avoid` funciona em blocos.
- **Ao testar PDFs**, extraia o texto com `gs -sDEVICE=txtwrite` gravando em **arquivo** (pela saída padrão
  o Ghostscript às vezes intercala uma mensagem de erro).
- **Defeitos da planilha original que NÃO foram copiados:** `#REF!` em "Rel Pagamentos", **total de despesas
  da conciliação somava só a 1ª nota**, blocos de nota com fórmulas inconsistentes e "VVV/PPP" de
  placeholder.
- **O LibreOffice não abre a planilha `.xlsx` do usuário** ("source file could not be loaded"); o layout foi
  deduzido lendo o XML da planilha.
- **No ambiente de desenvolvimento do Claude, Chrome e Firefox sem interface não geram capturas de tela.**
  Por isso a conferência visual das telas depende do **seu navegador**. (Os PDFs foram conferidos
  renderizando-os em imagem com o Ghostscript.)
- **Armadilha de shell:** `pkill -f padrão` pode casar com a própria linha de comando e encerrar o shell.
- **CSS:** uma `var(--x)` sem definição faz o navegador **ignorar a regra inteira**, em silêncio; há teste
  automático para isso.

---

## 8. Mapa do código

```
public/index.php          ponto de entrada (servidor embutido do PHP)
public/assets/            app.css (design system), app.js, favicon.svg, juncao.js, projeto-form.js, lancamento-form.js
src/bootstrap.php         monta rotas e dependências (52 rotas)
src/Core/                 Router, Request, Response, View, Csrf, Session, Database, Migrator, helpers
src/Controllers/          Municipio, Projeto, Documento, Juncao, Balancete, FisicoFinanceiro, Conciliacao
src/Services/             FileStorage, DocumentoService, JuncaoService, PdfMergeService, ConversionService,
                          ReportPdfService, BalanceteService, FisicoFinanceiroService, ConciliacaoService
src/Domain/               Money (centavos), Dates, Validator, BalanceteCalculator,
                          FisicoFinanceiroCalculator, ConciliacaoCalculator
src/Repositories/         acesso ao SQLite (um por assunto)
src/Catalog/DocumentCatalog.php   os 25 documentos (código, nome, comportamento, nome no ZIP)
templates/                telas (PHP); templates/pdf/ = PDFs dos 4 relatórios (NÃO mexer sem pedir)
database/migrations/      001 municípios e projetos · 002 documentos · 003 arquivos (junção) ·
                          004 lançamentos · 005 físico-financeiro · 006 conciliação e overrides
tests/                    247 testes (usam banco e storage temporários; nunca tocam no seu storage/)
specs/  plans/  docs/     documentação do processo
planilha/                 planilha de referência do usuário (só leitura; contém dados reais)
storage/                  DADOS: database.sqlite e PDFs (fora do versionamento)
plans/anexos/             backup do CSS original, de antes da fundação visual
```

**Regras de implementação importantes:** dinheiro **sempre em centavos** (inteiros); todo texto dinâmico
passa por `e()`; todo POST exige token CSRF; downloads só por id do banco (nunca por caminho); nomes de
arquivo no disco são aleatórios; o servidor só aceita `localhost`/`127.0.0.1`.

**Variáveis de ambiente opcionais:** `PRESTACAO_DB`, `PRESTACAO_STORAGE`, `PRESTACAO_GS`,
`PRESTACAO_SOFFICE`.

---

## 9. Próximos passos (ordem sugerida)

1. **Tela do Projeto (melhoria visual)** — escrever o **plano técnico** (`plans/melhoria-visual-tela-projeto-plan.md`),
   aprovar, autorizar e implementar. Rascunho do plano já conversado:
   - criar um **apresentador** (`src/Presenters/`) que calcula situação, detalhe e ação de cada uma das 25
     linhas (hoje essa lógica está repetida dentro de `templates/projetos/show.php`), com testes;
   - reescrever `templates/projetos/show.php`: cabeçalho com o objeto, cartão de dados, lista com 4 colunas,
     envio recolhido em `<details>`, zona de perigo para "Excluir";
   - nova seção no `app.css` e modo "cartões" em tela estreita (≤ 640 px);
   - **testes que leem o HTML da tela e precisam continuar passando:** 25× `class="tag vazio"`; 1×
     `class="tag ok"` após um envio; textos "Não gerado", "Desatualizado", "Convertido", "Abrir balancete",
     "Abrir físico-financeiro", "Abrir conciliação", "3 lançamento(s)", "1 linha(s)", "1 nota(s) fiscal(is)",
     `image/png,.png`, `.pptx`. O resumo **não** pode usar a classe `tag ok`.
   - decisões em aberto: **DEC-P1** (resumo "N de 25") e **DEC-P3** (textos provisórios).
2. **Etapa 9 — Relação de bens (doc. 09).** Falta a última parte da DEC-02: na planilha é **uma linha**
   ("1 — AQUISIÇÃO DE UM VEÍCULO ÔNIBUS", valor = total − rendimentos). Perguntar: pode ter **várias linhas**?
   o valor é calculado e editável? quais campos?
3. **Etapa 10 — ZIP** com todos os PDFs existentes, nomeados e numerados (`01-balancete.pdf`…), e
   mensagem quando não houver documento (EX-05).
4. **Etapa 11 — Validação final** contra os critérios CA-01 a CA-09 e relatório de pendências.
5. Demais telas da melhoria visual, uma por vez: lista de projetos → municípios e formulários →
   balancete → físico-financeiro → conciliação → junção → erros.
6. Reavaliar o ponto das **devoluções** na conciliação (§6) e as demais premissas provisórias.

---

## 10. Limitações conhecidas

- A melhoria visual foi verificada por testes, por HTML bem formado no servidor real e por cálculo de
  contraste, **mas não por captura de tela**. Defeitos de espaçamento só aparecem no navegador.
- O plano principal ainda tem duas linhas desatualizadas na Etapa 7 ("cálculo a partir do balancete,
  sobrescritas"): na implementação o físico-financeiro é **todo digitado**. A **spec** (v1.7+) é a referência.
- **Git:** repositório **público** em `https://github.com/PedroNiec/prestacao` (branch `main`). Primeiro commit em
  2026-11-12. `storage/` e `planilha/` (dados reais) ficam **fora** de propósito; o commit usa o e-mail pessoal
  do usuário. Antes de publicar, uma verificação de dados pessoais achou o nome da empresa no `composer.json`
  (pacote `ixcsoft/prestacao`), trocado por `pedroniec/prestacao`.
- Os PDFs não são idênticos pixel a pixel à planilha (decisão do usuário: "muito próximo").

---

## 11. Índice de documentos do projeto

| Arquivo | Conteúdo |
|---|---|
| `CLAUDE.md` | Instruções do projeto lidas pelo Claude ao iniciar |
| `.claude/skills/spec-driven/SKILL.md` | Skill `/spec-driven` (processo SDD) — **pasta oculta, copiar junto** |
| `specs/prestacao-de-contas.md` | Spec do sistema (v1.8): requisitos, regras, decisões e histórico |
| `plans/prestacao-de-contas-plan.md` | Plano técnico do sistema (11 etapas) |
| `specs/melhoria-visual-fundacao.md` e `plans/melhoria-visual-fundacao-plan.md` | Fundação visual |
| `specs/melhoria-visual-tela-projeto.md` | Spec da tela do Projeto (aprovada) |
| `specs/SPEC_TEMPLATE.md`, `plans/PLAN_TEMPLATE.md` | Modelos de documento |
| `README.md` | Como instalar e executar |
| `docs/GUIA-OUTRO-COMPUTADOR.md` | Como retomar o trabalho em outro computador e chamar o Claude |
