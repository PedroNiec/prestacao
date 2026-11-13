# Histórico do projeto — Sistema de Prestação de Contas

**Atualizado em:** 2026-11-12 (fim da segunda sessão do dia; tema da próxima sessão em §9, item 0)
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
| Execução | Local, no computador do usuário (`127.0.0.1:8000`, ou outra porta se a 8000 estiver ocupada); **não expor à internet** |
| Sistemas operacionais | **Linux** (onde foi desenvolvido) e **Windows 11** (onde o usuário trabalha agora; validado em 2026-11-12) |
| Tecnologia | **PHP 8.3+** (8.4 no Windows) + Composer, **SQLite**, Dompdf (PDF dos relatórios), Ghostscript (junção e conferência de páginas), LibreOffice (PowerPoint → PDF) |
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

**Como o usuário prefere conduzir** (observado em 2026-11-12):
- Aprova a spec "com as recomendações" e, em seguida, o plano e a autorização **numa pergunta só**.
- Prefere que todas as etapas sejam implementadas em sequência, com testes, e que o Claude **pare uma vez, no fim**,
  para a conferência no navegador.
- Para questões visuais, as escolhas funcionam melhor com **esboços (prévias em texto)** do que com perguntas
  abstratas. Ele quer que o sistema fique **bonito e moderno**; não tinha problemas pontuais de uso. Uma rodada de
  perguntas sobre "onde você trava" foi recusada.

---

## 3. Estado atual (2026-11-12)

**Testes: 328, todos passando neste Windows, nenhum pulado.** Rodar com `composer test` (cerca de 2 minutos).

**Git:** tudo **commitado na `main` e enviado ao GitHub** (`ba79583`). Diretório de trabalho limpo.

### 3.1 Sistema (plano `plans/prestacao-de-contas-plan.md`): **concluído**

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
| 9 | **Relação de bens** (09): várias linhas, tudo digitado, título editável, total (`specs/relacao-de-bens.md`) | ✔ 2026-11-12 |
| 10 | **ZIP da prestação** (`specs/zip-prestacao.md`): nomes do catálogo, aviso de desatualizados e não gerados | ✔ 2026-11-12 |
| 11 | **Validação final**: CA-01 a CA-09 atendidos (`docs/VALIDACAO-FINAL.md`) | ✔ 2026-11-12 |

**Depois do plano principal** (2026-11-12):

| Melhoria | Documento | Situação |
|---|---|---|
| **Suporte ao Windows**: o sistema encontra o Ghostscript e o LibreOffice sozinho; testes portáveis | `specs/suporte-windows.md` + plano | ✔ |
| **Balancete: lançar na própria tela**: formulário acima da tabela, linha nova destacada, formulário limpo após salvar | `specs/balancete-lancamento-na-tela.md` + plano | ✔ aprovado ("ficou bom") |

### 3.2 Visual da interface

| Fase | Documento | Situação |
|---|---|---|
| Fundação visual (tokens, componentes, `/guia-visual`) | `specs/melhoria-visual-fundacao.md` + plano | ✔ |
| 2.1 Tela do Projeto (reorganização) | `specs/melhoria-visual-tela-projeto.md` + plano | ✔ aprovada ("está ok") |
| 2.2 Demais telas (trilha, cartão do PDF, estados vazios, KPIs) | `specs/melhoria-visual-demais-telas.md` + plano | ✔ implementada. Ao ver, o usuário achou que o sistema **ainda tinha "cara antiga"** → fase 3 |
| **3.1 Novo layout (menu lateral claro) + Municípios** (piloto) | `specs/redesenho-layout-municipios.md` + plano | ✔ aprovada ("ficou perfeito, dessa maneira mesmo que quero seguir"). **É o padrão visual** |
| **3.2 Lista de Projetos** (cartões em linhas largas) | `specs/redesenho-lista-projetos.md` + plano | ✔ aprovada ("ficou boa") |
| 3.x Demais telas no estilo da fase 3 | — | ⏸ **pausado** a pedido do usuário (voltou às etapas técnicas). Ver §9 |

---

## 4. Linha do tempo resumida

1. **Descoberta:** entrevista sobre o problema (prestação de contas), usuários, formato das entradas
   e saídas, e análise da planilha `planilha/Balancete Prestação de Contas.xlsx`.
2. **Spec e plano** aprovados (`specs/prestacao-de-contas.md`; o histórico de versões da spec está na seção 14 dela,
   hoje na 1.11).
3. **Etapas 1 a 8**, cada uma com testes e verificação no servidor real. Em várias delas a revisão
   (inclusive visual, renderizando os PDFs em imagem) achou defeitos que os testes não pegavam; foram
   corrigidos e passaram a ter teste de regressão.
4. **Fundação visual** e primeiro commit no GitHub (2026-11-12, primeira sessão).
5. **2026-11-12, segunda sessão (no Windows)**:
   1. Primeiro `composer install` neste computador. Descoberto que **48 testes falhavam aqui** sem relação com o código
      (Ghostscript e LibreOffice ausentes, além de 2 diferenças de Windows). A lista foi guardada como referência
      para comparar "falhas novas".
   2. **Tela do Projeto** (fase 2.1): decisões DEC-P1 (sem resumo "N de 25") e DEC-P3 (manter textos); plano,
      apresentador `DocumentoLinhaPresenter` com prova de HTML idêntico antes da mudança visual; implementação.
   3. **Demais telas** (fase 2.2) numa spec só: trilha, cartão do PDF (`templates/partes/`), estados vazios, KPIs.
   4. O usuário achou a cara "antiga": **fase 3**, tela por tela. **Menu lateral claro** (escolhido entre 3 prévias),
      Municípios em cartões com iniciais e menu ⋮, e depois a lista de Projetos.
   5. **Etapa 9 — Relação de bens**: a planilha foi trazida para o computador e lida (aba "Rel de Bens construidos").
      Na planilha, o valor vinha do físico-financeiro; **o usuário preferiu tudo digitado**. Corrigidas as 2 falhas de
      Windows do `DesignSystemTest`.
   6. **Etapa 10 — ZIP.**
   7. **Etapa 11 — Validação final**: revelou que o sistema **nunca tinha sido validado no Windows** (junção, PNG e
      PowerPoint não funcionavam aqui). O usuário decidiu usar o Windows, e daí veio a spec de **suporte ao Windows**.
      Ghostscript e LibreOffice instalados; **328 testes passando**.
   8. **Balancete: lançar na própria tela** (pedido do usuário).
   9. Commits, merge na `main` e push.

---

## 5. Decisões de negócio tomadas pelo usuário

| Tema | Decisão |
|---|---|
| Plataforma | Web local, PHP, SQLite, sem login, uma pessoa; **usa no Windows** |
| Documentos | Todos os 25 em todo projeto; nenhum obrigatório; tipo de projeto (obra/serviço/bens) não muda nada |
| PDFs escaneados | Só anexados; **sem OCR** |
| Formulários | Balancete, físico-financeiro, conciliação e relação de bens são **preenchidos no sistema** (não mais na planilha) |
| Junção | Enviar vários, reordenar, juntar |
| Entrega | PDFs individuais **e** ZIP |
| Municípios | Cadastro reaproveitável; as assinaturas são **copiadas** para o projeto (mudar o município depois não altera projetos antigos) |
| Aparência dos PDFs | "Visual muito próximo da planilha" |
| Balancete | Receita = entrou na conta, Despesa = saiu (uma coluna cada); **origem** (Convênio/Rendimentos/Contrapartida) define a **fonte**; retenções (IRRF etc.) são **linhas de despesa separadas, sem tipo** |
| Balancete (tela) | **Novo lançamento na própria tela**; depois de salvar, o formulário **limpa tudo**; "Salvar e adicionar outro" removido; linha nova destacada; edição em página própria |
| Físico-financeiro | **Várias** metas/etapas; financeiro **sempre digitado** (não deriva do balancete); "no período" e "até o período" informados separadamente |
| Conciliação | Receitas vêm do balancete e são **editáveis** (com "alterado" e "Restaurar"); notas fiscais **digitadas** na própria conciliação; cabeçalho padrão do projeto; só o Prefeito assina; **Total de despesas = soma do bruto das notas** (**devoluções ficam fora do total**) |
| Relação de bens | **Várias linhas**; descrição e valor **digitados** (sem cálculo; na planilha vinha do físico-financeiro); 1º bem sugere o objeto; **título editável** (padrão "RELAÇÃO DE BENS ADQUIRIDOS"); **TOTAL sempre** |
| ZIP | Nomes **iguais ao download individual** (`01-balancete.pdf`…); nome do ZIP com o município; **desatualizados entram com aviso**; "não gerados" também avisados |
| Visual | Tela do Projeto **sem** o resumo "N de 25" (DEC-P1); **menu lateral claro**; cartões arredondados; ações secundárias no menu ⋮; "Excluir" desabilitado para município com projetos; lista de Projetos em **linhas largas**, cartão inteiro clicável, **sem barra de andamento** |
| Windows | Ghostscript e LibreOffice localizados automaticamente nas pastas padrão (DEC-W1) |

---

## 6. Premissas provisórias (assumidas por padrão; confirme quando puder)

| Código | Premissa | Onde mudar |
|---|---|---|
| DEC-01 | Aba "Dem Rec x Desp" da planilha **não** é gerada | spec principal |
| DEC-03 | CNO (doc. 17) aceita **só PDF** | `DocumentCatalog::extensoesUpload()` |
| DEC-04 | Junção aceita **só PDF** (não imagens) | `JuncaoService` |
| DEC-05 | Limite de **50 MB** por arquivo; backup = copiar a pasta `storage/` | `FileStorage::MAX_BYTES`, `composer serve` |
| DEC-08 | PowerPoint aceito só **`.pptx`** (não `.ppt`) | `FileStorage::MIMES`, catálogo |
| — | Linha **"SALDO (RECEITA − DESPESA)"** acrescentada ao PDF do balancete (a planilha não tem, mas o título cita "Saldo") | `templates/pdf/balancete.php` |
| — | CNPJ validado só pela quantidade de dígitos (14), não pelos dígitos verificadores | `Validator::lancamento()` |
| — | Receita digitada fora do balancete só nos 3 slots de repasse, 3 de contrapartida e 1 de rendimentos | `ConciliacaoCalculator` |

**Ponto que merece reconsideração:** como as devoluções ficam **fora** do total de despesas, um projeto
que devolveu saldo mostra **conciliação diferente de zero** (a tela avisa, mas o PDF sai com a diferença).
Se a planilha antiga fechava em zero nesses casos, vale mudar (é uma alteração pequena).

---

## 7. Descobertas técnicas importantes (armadilhas já resolvidas)

**PDF e ferramentas externas**
- **Ghostscript retorna código 0 mesmo sem conseguir ler um PDF** (por exemplo, protegido por senha): o
  PDF final sairia **sem aquelas páginas**, em silêncio. A junção tem 3 camadas: conta as páginas de
  cada origem, trata as mensagens de erro e confere o total de páginas do resultado. Nada é gravado se não conferir.
- **LibreOffice também retorna 0 quando falha** ao abrir um arquivo. A conversão valida a existência e a
  contagem de páginas do PDF gerado.
- **LibreOffice 26.x converte QUALQUER arquivo em PDF** (até texto renomeado para `.pptx`). Por isso o conversor
  confere antes se é um PowerPoint de verdade (ZIP com `ppt/presentation.xml`).
- **A conversão de PNG também depende do Ghostscript** (conferência de páginas). Sem ele, os docs 08 e 16 e a junção
  não funcionam.
- **Dompdf** ignora `<colgroup>`; no layout fixo, as larguras vêm da **primeira linha** da tabela. Não
  suporta `−` (U+2212) com as fontes padrão (usar `-`). `page-break-inside: avoid` funciona em blocos.
- **Ao testar PDFs**, extraia o texto com `gs -sDEVICE=txtwrite` gravando em **arquivo** (pela saída padrão
  o Ghostscript às vezes intercala uma mensagem de erro). Sem Ghostscript, dá para ler o texto descomprimindo as
  streams do PDF com PHP (`gzuncompress` + operadores `Tj`/`TJ`).
- **Para ver um PDF como imagem** (o Claude consegue olhar a imagem):
  `gswin64c -sDEVICE=png16m -r70 -dFirstPage=1 -dLastPage=1 -sOutputFile=x.png x.pdf`.

**Windows**
- No Windows, o Ghostscript se chama **`gswin64c.exe`** e o `soffice.exe` **não fica no PATH**. O perfil temporário do
  LibreOffice precisa de URL `file:///C:/…`. Tudo isso fica concentrado em `src/Services/Ferramentas.php`.
- **O Ghostscript não está no catálogo do `winget`** (o ID `ArtifexSoftware.GhostScript` não existe): instalar pelo
  site oficial. O LibreOffice está no `winget` (`TheDocumentFoundation.LibreOffice`).
- **`core.autocrlf=true`** converte LF em CRLF ao baixar os arquivos e mudava o hash dos templates de PDF; o teste
  agora normaliza as quebras de linha. Caminhos no Windows vêm com `\`, e o verificador de classes do CSS normaliza.
- **Armadilhas de shell (Git Bash):** heredoc às vezes **come barras invertidas** em código PHP; `php -r "..."` com
  `\\`, `$` e aspas é frágil. Escreva scripts em arquivo (ferramenta Write) e rode com `php arquivo.php`.

**Processo e testes**
- **Um teste que já falha por motivo de ambiente esconde regressões novas no mesmo teste.** Aconteceu com o verificador
  de classes do CSS. Confira o **conteúdo** da falha, não só a contagem.
- **Compare a suíte com uma lista de referência** de falhas quando o ambiente não está completo (`comm -13`).
- `View::render` passa `$titulo` para o layout (título da aba). Não reutilize essa chave para outra coisa (na relação de
  bens, o título do PDF virou `tituloDocumento`). Lembre também que `+` em arrays do PHP **mantém a chave da esquerda**.
- **Chaves de array como "11" viram inteiros** no PHP (afetou um teste do catálogo).
- `testNavigationMarksCurrentSection` exige o link do menu como `<a href="…" aria-current="page">Texto</a>`, **só com
  texto**. Por isso os ícones do menu ficam no CSS (`::before` com `mask`).

**Planilha e ambiente**
- **Defeitos da planilha original que NÃO foram copiados:** `#REF!` em "Rel Pagamentos", **total de despesas
  da conciliação somava só a 1ª nota**, blocos de nota com fórmulas inconsistentes e "VVV/PPP" de placeholder.
- **O LibreOffice não abre a planilha `.xlsx` do usuário**; ela é lida pelo XML (ZipArchive + `sharedStrings.xml` +
  `sheetN.xml`), o que mostra também as **fórmulas**.
- **No ambiente do Claude, navegadores sem interface não geram capturas de tela.** A conferência visual das
  telas depende do **navegador do usuário**; os PDFs são conferidos em imagem pelo Ghostscript.
- **CSS:** uma `var(--x)` sem definição faz o navegador **ignorar a regra inteira**, em silêncio; há teste para isso.
- **Armadilha de shell:** `pkill -f padrão` pode casar com a própria linha de comando e encerrar o shell.

---

## 8. Mapa do código

```
public/index.php          ponto de entrada (servidor embutido do PHP)
public/assets/            app.css (design system, 39,4 KB), app.js, favicon.svg, juncao.js, projeto-form.js, lancamento-form.js
src/bootstrap.php         monta rotas e dependências (62 rotas)
src/Core/                 Router, Request, Response (inclui tempFile: apaga depois de enviar), View, Csrf, Session,
                          Database, Migrator, helpers
src/Controllers/          Municipio, Projeto, Documento, Juncao, Balancete, FisicoFinanceiro, Conciliacao, Bens, Zip
src/Services/             FileStorage, DocumentoService, JuncaoService, PdfMergeService, ConversionService,
                          ReportPdfService, BalanceteService, FisicoFinanceiroService, ConciliacaoService,
                          BensService, ZipService, Ferramentas (localiza Ghostscript/LibreOffice)
src/Presenters/           DocumentoLinhaPresenter (situação/ação das 25 linhas + resumo do ZIP), Iniciais
src/Domain/               Money (centavos), Dates, Validator, BalanceteCalculator,
                          FisicoFinanceiroCalculator, ConciliacaoCalculator
src/Repositories/         acesso ao SQLite (um por assunto; BensRepository novo)
src/Catalog/DocumentCatalog.php   os 25 documentos (código, nome, comportamento, nome no ZIP)
templates/                telas (PHP); templates/partes/ = trilha e cartão do PDF (reaproveitados);
                          templates/balancete/_campos.php = campos do lançamento (tela e edição);
                          templates/pdf/ = PDFs dos 4 relatórios (protegidos por hash no DesignSystemTest)
database/migrations/      001 municípios e projetos · 002 documentos · 003 arquivos (junção) ·
                          004 lançamentos · 005 físico-financeiro · 006 conciliação e overrides · 007 relação de bens
tests/                    328 testes em 28 arquivos (banco e storage temporários; nunca tocam no seu storage/)
specs/  plans/  docs/     documentação do processo
planilha/                 planilha de referência do usuário (só leitura; dados reais; FORA do git)
storage/                  DADOS: database.sqlite e PDFs (FORA do git)
plans/anexos/             backup do CSS original, de antes da fundação visual
```

**Regras de implementação importantes:** dinheiro **sempre em centavos** (inteiros); todo texto dinâmico
passa por `e()`; todo POST exige token CSRF; downloads só por id do banco (nunca por caminho); nomes de
arquivo no disco são aleatórios; o servidor só aceita `localhost`/`127.0.0.1`; programas externos sempre com
`proc_open` e **lista de argumentos** (sem shell).

**Variáveis de ambiente opcionais:** `PRESTACAO_DB`, `PRESTACAO_STORAGE`, `PRESTACAO_GS`, `PRESTACAO_SOFFICE`.

---

## 9. Próximos passos (sugestão; o usuário escolhe)

0. **COMBINADO PARA A PRÓXIMA SESSÃO: conciliação × balancete.** Ao usar o sistema, o usuário lançou uma despesa
   no balancete e esperava vê-la na conciliação. **Hoje isso não acontece, por design (Etapa 8):** o balancete alimenta
   só as **receitas** da conciliação; as **despesas** vêm só das notas fiscais **digitadas na própria conciliação**.
   O resultado são duas versões da mesma prestação, que o sistema não cruza. No projeto de demonstração "Pavimentação
   asfáltica…", o balancete tinha 1 despesa de 743.376,14 (sem NF) e nenhuma receita, enquanto a conciliação tinha
   receitas digitadas à mão (1.500.000,00 + 960.000,00) e outra nota (NF 217, 307.395,92).
   **Proposta a discutir via `/spec-driven`:** a conciliação **montar as notas a partir do balancete**, agrupando as
   despesas pelo nº da NF (bruto = soma das linhas da NF; IR/ISS/INSS = linhas de retenção), editáveis com
   "alterado"/"Restaurar", como as receitas. **Decisões em aberto:** exigir o nº da NF nas despesas? Como
   identificar qual retenção é IR, ISS ou INSS? O que fazer com as notas já digitadas? Isso se junta à premissa das
   devoluções fora do total (§6).
1. **Retomar o redesenho visual (fase 3)**, tela por tela, no estilo de Municípios (`specs/redesenho-layout-municipios.md`,
   CSS seções 10 a 12). Sugestão de ordem: tela do Projeto → formulários → balancete, conciliação e físico-financeiro →
   junção. **Antes da próxima tela, decidir o tamanho do CSS** (39,4 de 40 KB). Recomendado: limpar regras antigas que
   não são mais usadas (ex.: `.topo`, a barra azul), o que exige ajustar o teste de nomes legados. Alternativa: subir
   o limite.
2. **Revisar as premissas provisórias** (§6), principalmente as **devoluções fora do total** da conciliação.
3. Pequenas melhorias anotadas:
   - o menu ⋮ dos municípios só fecha clicando nele (fechar ao clicar fora exigiria um pouco de JavaScript);
   - o resumo "N documento(s) no ZIP" conta registros, sem conferir o disco.
4. Usar o sistema com dados reais. Ao iniciar o servidor, a **migração 007** (tabelas da relação de bens) roda sozinha
   no banco real. Ela só cria tabelas.

---

## 10. Limitações conhecidas

- **Conferência visual:** as telas são verificadas por testes, por HTML bem formado no servidor real e por contraste,
  **mas não por captura de tela**. Defeitos de espaçamento só aparecem no navegador do usuário.
- O plano principal ainda tem duas linhas desatualizadas na Etapa 7 ("cálculo a partir do balancete,
  sobrescritas"): na implementação o físico-financeiro é **todo digitado**. A **spec** é a referência.
- O cabeçalho da spec principal diz "Versão: 1.0", mas o histórico dela (seção 14) vai até a **1.11**.
- **Git:** repositório **público** em `https://github.com/PedroNiec/prestacao` (branch `main`). `storage/` e
  `planilha/` (dados reais) ficam **fora** de propósito; o commit usa o e-mail pessoal do usuário. Os testes e um spec
  contêm os nomes do contador e do prefeito do exemplo da planilha; eles já estavam publicados desde o primeiro commit.
  O trabalho da segunda sessão de 2026-11-12 foi em 2 commits (suporte ao Windows; o resto) mais 2 de documentação,
  feitos num branch, juntados à `main` (fast-forward) e enviados; o branch foi apagado.
- **Tamanho do CSS:** `app.css` em 39,4 KB (limite do teste: 40 KB, elevado na DEC-L3).
- Os PDFs não são idênticos pixel a pixel à planilha (decisão do usuário: "muito próximo").
- **Neste computador Windows:**
  - foi preciso rodar `composer install`, porque a pasta `vendor/` não existia;
  - estão instalados o Ghostscript 10.08.0 (instalador oficial) e o LibreOffice 26.8.3 (`winget`);
  - a **porta 8000 está ocupada por outro projeto** (ReservaPlay). Rode na 8010 (README, "Porta ocupada?").
- Ghostscript e LibreOffice são instalados à parte; sem eles, junção, PNG e PowerPoint não funcionam (README).

---

## 11. Índice de documentos do projeto

| Arquivo | Conteúdo |
|---|---|
| `CLAUDE.md` | Instruções do projeto lidas pelo Claude ao iniciar |
| `.claude/skills/spec-driven/SKILL.md` | Skill `/spec-driven` (processo SDD) — **pasta oculta, copiar junto** |
| `README.md` | Como instalar e executar (Linux e Windows), backup, testes |
| `docs/GUIA-OUTRO-COMPUTADOR.md` | Como retomar o trabalho em outro computador e chamar o Claude |
| `docs/VALIDACAO-FINAL.md` | Validação final (Etapa 11): critérios, evidências, defeitos corrigidos e pendências |
| `specs/prestacao-de-contas.md` · `plans/prestacao-de-contas-plan.md` | Spec e plano do sistema (11 etapas) |
| `specs/relacao-de-bens.md` · `specs/zip-prestacao.md` (+ planos) | Etapas 9 e 10 |
| `specs/suporte-windows.md` (+ plano) | Suporte ao Windows |
| `specs/balancete-lancamento-na-tela.md` (+ plano) | Balancete: lançar na própria tela |
| `specs/melhoria-visual-fundacao.md` (+ plano) | Fundação visual |
| `specs/melhoria-visual-tela-projeto.md` · `specs/melhoria-visual-demais-telas.md` (+ planos) | Visual fases 2.1 e 2.2 |
| `specs/redesenho-layout-municipios.md` · `specs/redesenho-lista-projetos.md` (+ planos) | Visual fase 3 (padrão atual) |
| `specs/SPEC_TEMPLATE.md`, `plans/PLAN_TEMPLATE.md` | Modelos de documento |
