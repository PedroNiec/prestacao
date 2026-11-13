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

**Testes: 328 — todos passando neste Windows, nenhum pulado** (2026-11-12, com Ghostscript 10.08.0 e LibreOffice 26.8.3). Rodar com `composer test`.

> **Windows (2026-11-12):** Ghostscript 10.08.0 (instalador oficial; **não está no `winget`**) e LibreOffice 26.8.3
> (`winget`) instalados neste computador; o sistema os encontra sozinho (`src/Services/Ferramentas.php`). Com eles, a
> suíte inteira passa aqui. Em outro computador sem as ferramentas, junção, PNG e PowerPoint não funcionam (README).

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
| 9 | **Relação de bens** (09): várias linhas, tudo digitado, título editável, total (`specs/relacao-de-bens.md`) | ✔ |
| 10 | **Entrega: ZIP numerado** (`specs/zip-prestacao.md`): nomes do catálogo, aviso de desatualizados/não gerados | ✔ |
| 11 | Validação final: CA-01 a CA-09 atendidos (`docs/VALIDACAO-FINAL.md`) | ✔ |

> **Etapas 1 a 11 concluídas.** O sistema também roda no Windows (`specs/suporte-windows.md`).
> **Depois da Etapa 11:** balancete com o **formulário de novo lançamento na própria tela**
> (`specs/balancete-lancamento-na-tela.md`): lança vendo a lista e os totais; a linha nova fica destacada; o
> "Salvar e adicionar outro" saiu; a edição continua em página própria. Aprovado pelo usuário.

### 3.2 Melhoria visual da interface (iniciada depois da Etapa 8)

| Fase | Documento | Situação |
|---|---|---|
| Levantamento visual | (relatório na conversa) | ✔ concluído |
| **Fundação visual** | `specs/melhoria-visual-fundacao.md` + `plans/melhoria-visual-fundacao-plan.md` | ✔ **implementada**; você conferiu e não achou nada estranho |
| **Tela do Projeto** | `specs/melhoria-visual-tela-projeto.md` (v1.3) + `plans/melhoria-visual-tela-projeto-plan.md` | ✔ **implementada**; **falta você conferir no navegador** (desktop, 360 px, teclado) |
| **Redesenho (fase 3)**: menu lateral claro + Municípios | `specs/redesenho-layout-municipios.md` + plano | ✔ **concluído e aprovado** ("ficou perfeito, dessa maneira mesmo que quero seguir"): é o **padrão visual** das próximas telas. O usuário achou que o sistema "ainda tinha cara antiga" após a fase 2 e pediu redesenho **tela por tela**, começando por Municípios (piloto do estilo) |
| **Demais telas** (todas) | `specs/melhoria-visual-demais-telas.md` + `plans/melhoria-visual-demais-telas-plan.md` | ✔ **implementadas** numa spec só (escolha do usuário); **falta você conferir no navegador** |

---

## 4. Linha do tempo resumida

1. **Descoberta:** entrevista sobre o problema (prestação de contas), usuários, formato das entradas
   e saídas, e análise da planilha `planilha/Balancete Prestação de Contas.xlsx`.
2. **Spec e plano** aprovados (spec v1.8 hoje; ver `specs/prestacao-de-contas.md`, seção 14).
3. **Etapas 1 a 8**, cada uma com testes e verificação no servidor real. Em várias delas a revisão
   (inclusive visual, renderizando os PDFs em imagem) achou defeitos que os testes não pegavam; foram
   corrigidos e passaram a ter teste de regressão.
4. **Melhoria visual:** levantamento → fundação visual (tokens de design, componentes, layout, página
   `/guia-visual`) → tela do Projeto (spec, plano e implementação em 2026-11-12: apresentador
   `src/Presenters/DocumentoLinhaPresenter.php`, novo `show.php` e seção 8 do `app.css`).

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

1. **Conferir no navegador a tela do Projeto e as demais telas** (falta só isso). A tela do Projeto já foi vista e aprovada ("está ok").
   Nas demais, olhar também: arrastar na junção, preenchimento automático no projeto e fonte automática no lançamento.
   Na tela do Projeto: desktop, largura de 360 px (F12 → modo dispositivo),
   Tab pela lista, abrir "Enviar arquivo ▾" e enviar, voltar para a linha (`#doc-XX`) sem ficar sob a mensagem.
   Se algo estiver estranho, ajustar no `app.css` (seção 8). Plano B da âncora: `scroll-padding-top` via
   `html:has(.docs)`.
2. ~~Etapa 9 — Relação de bens~~ **feita** (2026-11-12). Decisões: várias linhas, **tudo digitado** (na planilha o
   valor vinha do físico-financeiro: realizado até o período − rendimentos; o usuário preferiu digitar), título
   editável, total sempre. Falta o usuário conferir o PDF.
3. ~~Etapa 10 — ZIP~~ **feita** (2026-11-12): botão "Baixar prestação (ZIP)" na tela do Projeto. Falta o usuário abrir
   o ZIP no Windows.
4. ~~Etapa 11 — Validação final~~ **feita** (2026-11-12): `docs/VALIDACAO-FINAL.md` (CA-01 a CA-09 ✔, pendências na §5).
5. **Redesenho tela por tela (fase 3)**: Municípios feito (piloto, aprovado); **lista de Projetos aprovada**
   (`specs/redesenho-lista-projetos.md`). **Pausado a pedido do usuário** para voltar às etapas técnicas (9–11).
   Ao retomar: **CSS em 39,2 KB, quase no limite de 40 KB**; decidir antes da próxima tela entre limpar as regras
   antigas (recomendado) ou subir o limite (ver §10). Próximas: escolher com o usuário (sugestão: tela do Projeto → formulários → balancete/conciliação/físico → junção). Cada uma com spec curta.
   Lição: um teste que já falha por ambiente (ex.: classes do `DesignSystemTest`) **esconde regressões**; conferir o
   conteúdo da falha, não só a contagem.
6. ~~Demais telas da melhoria visual~~ (feito em 2026-11-12; peças comuns em `templates/partes/`: `trilha.php` e `pdf.php`).
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
  **Trabalho de 2026-11-12 (segunda sessão)**: commits feitos no branch `melhorias-2026-11-12` (suporte ao Windows;
  etapas 9–11 + redesenho + balancete na tela), juntados à `main` (fast-forward) e enviados ao GitHub a pedido do usuário. Os testes e um spec repetem os nomes do contador e do prefeito do exemplo, que já estavam publicados.
- **Tamanho do CSS:** `app.css` em 39,4 KB (limite do teste: 40 KB, elevado na DEC-L3). Ainda há regras da
  fundação que as telas redesenhadas não usam mais (ex.: `.topo`, mantidas por um teste de nomes legados).
- Os PDFs não são idênticos pixel a pixel à planilha (decisão do usuário: "muito próximo").
- **Neste computador Windows** (2026-11-12): faltava `vendor/` (rodar `composer install`); Ghostscript e LibreOffice
  foram instalados (ver nota no §3). A **porta 8000 está ocupada por outro projeto** (ReservaPlay): rode em outra
  porta (README, "Porta ocupada?").

---

## 11. Índice de documentos do projeto

| Arquivo | Conteúdo |
|---|---|
| `CLAUDE.md` | Instruções do projeto lidas pelo Claude ao iniciar |
| `.claude/skills/spec-driven/SKILL.md` | Skill `/spec-driven` (processo SDD) — **pasta oculta, copiar junto** |
| `specs/prestacao-de-contas.md` | Spec do sistema (v1.8): requisitos, regras, decisões e histórico |
| `plans/prestacao-de-contas-plan.md` | Plano técnico do sistema (11 etapas) |
| `specs/melhoria-visual-fundacao.md` e `plans/melhoria-visual-fundacao-plan.md` | Fundação visual |
| `specs/melhoria-visual-tela-projeto.md` e `plans/melhoria-visual-tela-projeto-plan.md` | Tela do Projeto (implementada) |
| `specs/SPEC_TEMPLATE.md`, `plans/PLAN_TEMPLATE.md` | Modelos de documento |
| `docs/VALIDACAO-FINAL.md` | Validação final (Etapa 11): critérios, evidências e pendências |
| `specs/relacao-de-bens.md`, `specs/zip-prestacao.md`, `specs/suporte-windows.md` (+ planos) | Etapas 9, 10 e suporte ao Windows |
| `specs/redesenho-*.md` (+ planos) | Redesenho visual fase 3 (menu lateral, Municípios, lista de Projetos) |
| `README.md` | Como instalar e executar | (inclui Windows)
| `docs/GUIA-OUTRO-COMPUTADOR.md` | Como retomar o trabalho em outro computador e chamar o Claude |
