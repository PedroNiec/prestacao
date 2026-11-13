# PLAN — Sistema de Prestação de Contas

**Versão:** 1.0
**Status:** Aprovado (2026-11-12)
**SPEC relacionada:** specs/prestacao-de-contas.md (v1.1, aprovada em 2026-11-12)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Aplicação web local em **PHP 8.3 puro** (sem framework), organizada em camadas enxutas
(controllers, serviços, repositórios, views), com **SQLite** para dados e o sistema de
arquivos para os PDFs. O servidor roda com `php -S` (servidor embutido) em `localhost`.

- Documentos de **junção**: upload, reordenação e fusão via **Ghostscript** (`gs`, já instalado).
- Documentos de **upload**: armazenamento do arquivo.
- **Conversões**: PowerPoint → PDF via **LibreOffice** (headless); PNG → PDF via biblioteca de PDF.
- **Relatórios (1, 9, 15, 22)**: dados em SQLite, valores calculados por serviços de domínio
  (dinheiro em centavos, inteiros) e PDF gerado por **Dompdf** a partir de templates HTML/CSS.
- **ZIP** com `ZipArchive` (extensão `zip` já presente).

## 2. Análise da arquitetura atual

O projeto não tem código. Existem apenas `CLAUDE.md`, os templates de `specs/` e `plans/`
e a planilha de referência `planilha/Balancete Prestação de Contas.xlsx`.

Ambiente verificado:

| Item | Situação | Ação |
|---|---|---|
| PHP 8.3.6, Composer | OK | — |
| Ghostscript 10.02 | OK | Usado na junção de PDFs |
| Extensões `zip`, `mbstring`, `intl`, `fileinfo`, `dom` | OK | — |
| `pdo_sqlite` | **Ausente** | `sudo apt install php8.3-sqlite3` |
| `gd` | **Ausente** | `sudo apt install php8.3-gd` (necessária para PNG no Dompdf) |
| LibreOffice | **Ausente** | `sudo apt install libreoffice-impress` (somente doc. 8) |

Comportamento observado na planilha (base das regras):

- Cabeçalho (executor, concedente, processo, PP/NE/NL, objeto, local/data, signatários)
  é digitado uma vez e referenciado nas demais abas.
- `Rel Pagamentos` é a fonte central: seus totais alimentam o físico-financeiro e a conciliação.
- A planilha tem defeitos que **não** devem ser reproduzidos: `#REF!` em `Rel Pagamentos!N34`,
  linha "Outros descontos" (`C41`) dentro de um bloco de nota fiscal, blocos de nota fiscal
  da conciliação com fórmulas inconsistentes e uma célula solta (`Rel Fis x Fin!O5`).

## 3. Arquivos envolvidos

Todos serão **criados** (projeto novo). Exceção: `specs/prestacao-de-contas.md` (já atualizado).

| Arquivo / pasta | Ação | Justificativa |
|---|---|---|
| `composer.json` | Criar | Autoload PSR-4 e dependências (`dompdf/dompdf`; dev: `phpunit/phpunit`) |
| `public/index.php` | Criar | Ponto de entrada e roteador |
| `public/assets/` (css, js) | Criar | Estilos e JS (arrastar e soltar na reordenação) sem libs externas |
| `src/Core/` (Router, Database, View, Request, Csrf, Migrator) | Criar | Infra mínima da aplicação |
| `src/Controllers/` | Criar | Municípios, Projetos, Documentos, Balancete, FisicoFinanceiro, Conciliação, Bens, Download |
| `src/Services/` | Criar | `PdfMergeService`, `ConversionService`, `ReportPdfService`, `ZipService`, `FileStorage` |
| `src/Domain/` | Criar | `Money`, cálculos de Balancete, Conciliação, Físico-financeiro, Bens, `Override` |
| `src/Repositories/` | Criar | Acesso ao SQLite (PDO) |
| `src/Catalog/DocumentCatalog.php` | Criar | Lista fixa dos 25 documentos, tipo de comportamento e nome no ZIP |
| `templates/` | Criar | Views HTML e templates de PDF dos 4 relatórios |
| `database/migrations/*.sql` | Criar | Esquema do banco |
| `storage/` (db, uploads, generated) | Criar | Dados locais; fora do versionamento |
| `tests/` | Criar | Testes unitários e de integração |
| `README.md` | Criar | Pré-requisitos, instalação e execução |
| `.gitignore` | Criar | Ignora `storage/`, `vendor/` |

## 4. Banco de dados

SQLite (`storage/database.sqlite`), com `PRAGMA foreign_keys = ON`. Valores monetários em
**centavos (INTEGER)**. Migrações em SQL numeradas, executadas por `Migrator`.

| Tabela | Campos principais |
|---|---|
| `municipios` | id, nome, uf, prefeito_nome, contador_nome, contador_crc |
| `projetos` | id, municipio_id (FK), concedente, processo, nr_pp, nr_ne, nr_nl, objeto, local, data_assinatura, prefeito_nome, contador_nome, contador_crc |
| `arquivos` | id, projeto_id (FK), doc_codigo, ordem, nome_original, caminho, tamanho, criado_em |
| `documentos` (nome final; era `documentos_gerados`) | id, projeto_id (FK, cascade), doc_codigo, caminho, nome_original, tamanho, atualizado_em; único (projeto_id, doc_codigo). Guarda o PDF final de cada documento (o próprio upload ou o PDF gerado) |
| `lancamentos` | id, projeto_id (FK), ordem, boletim_parcela, descricao, cnpj, nota_fiscal, nota_empenho, doc, data, origem (convenio/rendimentos/contrapartida), fonte (1/2), tipo (receita/despesa), valor_centavos. Retenções são linhas de despesa comuns (sem tipo de retenção, decisão do usuário) |
| `fisico_financeiro` (era `fisico_metas`) | id, projeto_id (FK), ordem, meta, etapa, descricao, unidade; físico: prog/exec no período e até o período (centésimos); financeiro: concedente/executor/outros rendimentos no período e concedente/executor até o período (centavos). Tudo digitado |
| `conciliacao`, `conciliacao_notas` | `conciliacao` (1 por projeto): título, rótulo e descrição do extrato, data do saldo, saldos da conta corrente e da aplicação, 5 devoluções. `conciliacao_notas`: nota fiscal, bruto, IR, ISS, INSS, outros (digitados) |
| `bens` | id, projeto_id (FK), ordem, descricao, valor_centavos |
| `overrides` | id, projeto_id (FK), relatorio, campo, valor_centavos (valor calculado sobrescrito; apagar a linha restaura o calculado). Usado nas receitas da conciliação |

Índices: `arquivos(projeto_id, doc_codigo, ordem)`, `lancamentos(projeto_id, ordem)`,
`overrides(projeto_id, relatorio, campo)` único. Exclusão de município com projetos é
**bloqueada** (EX-04).

> Os campos finais de `lancamentos`, `fisico_metas` e `bens` dependem de DEC-02 da spec.

## 5. Serviços e regras de negócio

- **`DocumentCatalog`**: define os 25 documentos (código, nome, comportamento: `merge`,
  `upload`, `convert_pptx`, `convert_png`, `report`) e o nome no ZIP (`01-balancete.pdf`...).
- **`PdfMergeService`**: valida cada arquivo (tipo real via `fileinfo`, cabeçalho `%PDF`),
  executa `gs -dBATCH -dNOPAUSE -sDEVICE=pdfwrite` com argumentos escapados e grava o PDF final.
  Erros indicam o arquivo problemático (EX-02).
- **`ConversionService`**: PPTX → PDF com `soffice --headless --convert-to pdf` (perfil
  temporário próprio, com timeout); PNG → PDF via Dompdf/GD (EX-03).
- **`Money`**: parsing/formatação BR (`1.234,56`), soma/subtração em inteiros.
- **`BalanceteCalculator`** (RN-04, RN-08): totais gerais, por origem (convênio, rendimentos,
  contrapartida) e resumo.
- **`ConciliacaoCalculator`** (RN-05): receitas (repasses, contrapartidas, rendimentos) e, por
  nota fiscal, líquido = bruto − IR − ISS − INSS − outros, com somas acumuladas.
- **`FisicoFinanceiroCalculator`** (RN-06): financeiro do período e até o período.
- **`BensCalculator`**: relação de bens e total (hoje = total do projeto − rendimentos).
- **`OverrideResolver`** (RN-07): retorna o valor sobrescrito, se existir, senão o calculado;
  expõe "foi sobrescrito?" para a sinalização visual.
- **`ReportPdfService`**: renderiza o template HTML de cada relatório e gera o PDF com Dompdf.
- **`ZipService`**: monta o ZIP com os PDFs existentes (EX-05).
- **`FileStorage`**: grava uploads com nomes gerados (nunca o nome do usuário), valida
  tamanho e extensão e remove arquivos órfãos.

## 6. Fluxo técnico

1. Navegador → `public/index.php` → `Router` → `Controller`.
2. Requisições `POST`: validar token CSRF → validar entrada → serviço/repositório → SQLite → redirecionar.
3. **Junção**: upload (`multipart`) → `FileStorage` → registro em `arquivos` com `ordem` →
   reordenação via JS (`POST` da nova ordem) → "Gerar PDF" → `PdfMergeService` →
   `storage/generated/` + `documentos_gerados`.
4. **Upload/conversão**: arquivo validado → (converter, se aplicável) → `documentos_gerados`.
5. **Relatórios**: formulário grava lançamentos/metas/bens → calculadores produzem valores →
   `OverrideResolver` aplica sobrescritas → tela mostra o relatório (campos alterados marcados) →
   "Gerar PDF" → `ReportPdfService`.
6. **Download**: `GET` individual ou ZIP; o controller só serve arquivos registrados no banco
   (nunca por caminho informado pelo usuário).

## 7. Segurança e permissões

- Uso local e sem login (spec). O servidor deve ouvir **apenas em `127.0.0.1`**; o README
  documentará isso.
- **CSRF** em todos os `POST` (mesmo local, um site aberto no navegador pode atacar `localhost`).
- Validação de uploads: extensão + tipo MIME real (`fileinfo`) + tamanho máximo; nomes
  gerados; arquivos fora de `public/`.
- Prevenção de **path traversal**: downloads por id do banco, jamais por caminho.
- `escapeshellarg` e array de argumentos ao chamar `gs` e `soffice`; timeouts.
- Escape de saída (`htmlspecialchars`) nas views e nos templates de PDF; PDO com *prepared statements*.
- Dompdf com acesso remoto desabilitado (`isRemoteEnabled = false`).

## 8. Riscos e impactos

Não há funcionalidades existentes (projeto novo). Riscos do próprio projeto:

| Risco | Impacto | Mitigação |
|---|---|---|
| PDF dos relatórios diferente da planilha | Prefeitura/concedente rejeita o documento | Reproduzir colunas, ordem e rodapé da planilha; comparar lado a lado (CA-08) |
| Fórmulas divergentes da planilha | Totais incorretos em documento oficial | Testes automatizados com os valores da planilha de exemplo (CA-06) |
| Dompdf com tabelas largas / quebra de página | Layout quebrado | Orientação paisagem e CSS de impressão; teste visual por relatório |
| LibreOffice indisponível ou lento | Doc. 8 não converte; **o LibreOffice sai com código 0 mesmo ao falhar** (verificado) | Mensagem clara com instrução de instalação; resultado validado por existência e contagem de páginas do PDF; timeout; pasta temporária sempre removida; só afeta o doc. 8 |
| PDFs protegidos/corrompidos na junção | Junção falha **ou, pior, perde páginas em silêncio** (o Ghostscript retorna código 0 mesmo sem conseguir ler o arquivo — verificado) | Contagem de páginas de cada origem antes, tratamento das mensagens de erro e conferência do total de páginas do PDF final; nada é gravado se não conferir (EX-02) |
| Sobrescritas desatualizadas após alterar lançamentos | Valores inconsistentes | Sinalização visual, restauração fácil e aviso quando houver sobrescritas |
| Perda de dados (arquivo SQLite local) | Perda de trabalho | Backup simples (copiar `storage/`); decisão em DEC-05 |
| Arredondamento | Erros em centavos | Dinheiro como inteiros (`Money`) |

## 9. Estratégia de implementação

Cada etapa termina com um checkpoint para você validar antes da próxima.

- [x] Etapa 1: **Base** — `composer.json`, estrutura de pastas, roteador, banco/migrações, layout base, CSRF e README. Requer instalar `php8.3-sqlite3` e `php8.3-gd` e rodar `composer install`.
- [x] Etapa 2: **Cadastros** — municípios e projetos (CRUD, herança de signatários), tela do projeto com os 25 documentos (`DocumentCatalog`).
- [x] Etapa 3: **Upload** — armazenamento seguro, substituição e remoção (11 documentos de upload).
- [x] Etapa 4: **Junção de PDFs** — upload múltiplo, reordenação, geração e download (8 documentos).
- [x] Etapa 5: **Conversões** — PNG → PDF (doc. 16) e PPTX → PDF (doc. 8, requer LibreOffice).
- [x] Etapa 6: **Balancete (doc. 1)** — lançamentos, totais e PDF.
- [x] Etapa 7: **Físico-financeiro (doc. 22)** — cálculo a partir do balancete, sobrescritas e PDF.
- [x] Etapa 8: **Conciliação bancária (doc. 15)** — cálculo, sobrescritas e PDF.
- [x] Etapa 9: **Relação de bens (doc. 9)** *(2026-11-12; ver `specs/relacao-de-bens.md`)* — itens, total e PDF.
- [x] Etapa 10: **Entrega** *(2026-11-12; ver `specs/zip-prestacao.md`)* — ZIP numerado, download individual e acabamento.
- [x] Etapa 11: **Validação final** *(2026-11-12; relatório em `docs/VALIDACAO-FINAL.md`)* — critérios de aceitação, relatório e pendências.

## 10. Testes e validações

PHPUnit para domínio e serviços; verificação manual para a interface e o visual dos PDFs.

- [ ] Teste funcional: fluxo completo (município → projeto → documentos → ZIP).
- [ ] Teste de regras de negócio: `Money`, calculadores e `OverrideResolver`, com os valores da planilha de exemplo (receita 497.152,49; IRRF 5.965,83; líquido 491.186,66).
- [ ] Teste de exceções: EX-01 a EX-06 (arquivo inválido, PDF corrompido, falha de conversão, excluir município com projetos, ZIP vazio, arquivo grande).
- [ ] Teste de segurança: CSRF, upload malicioso (extensão dupla, MIME falso), path traversal.
- [ ] Teste visual: comparar cada PDF de relatório com o PDF impresso da planilha.
- [ ] Teste de regressão: suíte automatizada executada a cada etapa.

## 11. Critérios de conclusão

- Todos os critérios CA-01 a CA-09 da spec atendidos.
- Testes automatizados passando e verificação manual dos PDFs concluída.
- README com pré-requisitos, instalação, execução e backup.
- Pendências e limitações documentadas no relatório final.

## 12. Decisões técnicas pendentes

- [ ] DT-01: Aprovar as dependências Composer: `dompdf/dompdf` (PDF dos relatórios) e `phpunit/phpunit` (somente dev). Alternativa: `mpdf/mpdf`.
- [ ] DT-02: Aprovar a instalação dos pacotes do sistema: `php8.3-sqlite3`, `php8.3-gd`, `libreoffice-impress` (este só para o doc. 8). **Eu não instalo; você executa.**
- [ ] DT-03: Aprovar Ghostscript para a junção. Dependência de runtime já instalada; não é Composer.
- [ ] DT-04: Pendências DEC-01 a DEC-07 da spec, em especial DEC-02 (campos dos relatórios), antes das etapas 6 a 9. Tratar uma a uma, em cada etapa.
- [ ] DT-05: Tamanho máximo de upload (sugestão: 50 MB por arquivo, ajustando `upload_max_filesize` e `post_max_size`).

## 13. Aprovação

- [x] Especificação aprovada (2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12).
