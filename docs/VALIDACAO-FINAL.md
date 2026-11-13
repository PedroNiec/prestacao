# Validação final — Sistema de Prestação de Contas (Etapa 11)

**Data:** 2026-11-12
**Ambiente:** Windows 11 · PHP 8.4.26 · Ghostscript 10.08.0 · LibreOffice 26.8.3 · SQLite
**Referências:** `specs/prestacao-de-contas.md` (v1.10) · `plans/prestacao-de-contas-plan.md`

---

## 1. Resultado

| Item | Resultado |
|---|---|
| Testes automatizados | **322 de 322 passando**, nenhum pulado (`composer test`, 1:47 min) |
| Critérios de aceitação CA-01 a CA-09 | **9 de 9 atendidos** (ver §2) |
| Exceções EX-01 a EX-06 | **6 de 6 cobertas por testes** (ver §3) |
| Etapas do plano principal | 1 a 11 concluídas |

Antes desta validação o sistema só tinha sido validado no Linux. Ela revelou que, no Windows, a junção de PDFs e as
conversões (PNG e PowerPoint) **não funcionavam**. O problema foi resolvido em `specs/suporte-windows.md`: o sistema
passou a localizar o Ghostscript e o LibreOffice sozinho, e os testes ficaram portáveis. A validação também
encontrou e corrigiu uma brecha: o LibreOffice 26.x converte qualquer arquivo em PDF, até um que não é uma
apresentação.

## 2. Critérios de aceitação

| Critério | Situação | Evidência |
|---|---|---|
| **CA-01** Cadastrar município e projeto; cabeçalho e assinaturas automáticos nos relatórios | ✔ | `CadastrosTest` (assinaturas copiadas do município); cabeçalho e assinaturas conferidos no texto dos 4 PDFs (`BalanceteFluxoTest`, `ConciliacaoTest`, `FisicoFinanceiroTest`, `RelacaoBensTest`) |
| **CA-02** 25 documentos em todo projeto, nenhum obrigatório | ✔ | `CadastrosTest` (25 linhas "Vazio"), `TelaProjetoTest`, `DocumentoLinhaPresenterTest` |
| **CA-03** Juntar vários PDFs em ordem definida e baixar | ✔ | `JuncaoTest` 19/19 e `PdfMergeServiceTest` 7/7 neste Windows; no servidor real, 2 PDFs juntados na ordem NOTA-A-1, NOTA-A-2, NOTA-B-1 |
| **CA-04** Upload, substituir e remover | ✔ | `UploadFlowTest` 11/11, `FileStorageTest` 12/12 |
| **CA-05** Converter PowerPoint e PNG em PDF | ✔ | `ConversionServiceTest` e `ConversaoFluxoTest` sem pulados; no servidor real, PPTX (2 slides) e PNG convertidos |
| **CA-06** Totais da planilha de exemplo em todos os relatórios | ✔ | 497.152,49 · 5.965,83 · 491.186,66 conferidos na tela e no texto dos PDFs (`BalanceteFluxoTest::testGeneratedPdfHasHeaderRowsTotalsAndSignatures`, `ConciliacaoTest::testPdfReproducesSpreadsheetExampleOnOnePage`, `FisicoFinanceiroTest::testGeneratedPdfMatchesSpreadsheetExample`). A relação de bens é digitada (decisão de 2026-11-12) |
| **CA-07** Sobrescrever um valor calculado, ver sinalizado e restaurar | ✔ | `ConciliacaoTest::testOverrideIsSavedMarkedAndRestorable` e mais 5 testes de sobrescrita |
| **CA-08** PDFs dos 4 relatórios com visual muito próximo ao da planilha | ✔ | Balancete, físico-financeiro e conciliação conferidos em imagem nas etapas 6 a 8; relação de bens e conciliação renderizadas em imagem nesta validação (layout da aba da planilha: cabeçalho, título, itens, total, local/data, assinaturas). A comparação final com o papel é do usuário |
| **CA-09** Baixar cada PDF individual e o ZIP numerados | ✔ | `UploadFlowTest::testDownloadServesRegisteredPdf`, `ZipTest` 7/7; ZIP real com 4 PDFs idênticos (hash) aos downloads individuais |

## 3. Exceções e segurança

| Exceção | Testes |
|---|---|
| EX-01 Arquivo inválido | `testRejectsFakePdfByContent`, `testRejectsWrongExtension`, `testRejectsDoubleExtensionTrick`, `testFakePngIsRejectedByContent`, `testZipThatIsNotAPresentationIsRejectedBeforeLibreOffice` |
| EX-02 PDF protegido ou corrompido na junção | `testPasswordProtectedPdfIsIdentifiedByName`, `testCorruptedPdfIsReportedByNameAndNothingIsSaved` |
| EX-03 Falha na conversão | `testFailedPptxConversionKeepsPreviousDocumentAndLeavesNoGarbage`, `testFakePptxFailsWithClearMessageAndCleansUp`, `testCorruptedPngIsRejected` |
| EX-04 Excluir município com projetos | `MunicipiosTelaTest::testServerStillRefusesDeletingMunicipioWithProjects` |
| EX-05 ZIP sem documentos | `ZipTest::testNoPdfRedirectsWithMessage` |
| EX-06 Arquivo acima do limite | `FileStorageTest::testRejectsFileOverLimit` |
| Segurança | CSRF (`testPostWithoutCsrfIsRejected` e um teste por formulário), acesso só por localhost (`testRejectsForeignHost`), caminho malicioso (`testAbsoluteRejectsTraversal`, `testUserNameCannotInjectPath`), escape de HTML em todas as telas e nos PDFs |

## 4. Defeitos encontrados e corrigidos nesta validação

| Defeito | Correção |
|---|---|
| Junção, PNG e PowerPoint não funcionavam no Windows (Ghostscript se chama `gswin64c`; LibreOffice fora do PATH; URL de perfil de Linux) | `src/Services/Ferramentas.php` + serviços ajustados (`specs/suporte-windows.md`) |
| 48 testes não rodavam no Windows (`gs`/`soffice` fixos, `/dev/null`) | `tests/Support.php` sem shell |
| LibreOffice 26.x converte um arquivo que não é apresentação | O conversor confere que é um `.pptx` de verdade antes de chamar o LibreOffice |
| README só com instruções de Linux | Seção Windows (Ghostscript pelo instalador oficial, porque não está no `winget`; LibreOffice pelo `winget`; porta alternativa) |

## 5. Pendências e decisões provisórias

**Decisões provisórias** (funcionam com o padrão; confirmar quando possível):

| Código | Padrão em uso |
|---|---|
| DEC-01 | Aba "Dem Rec x Desp" não é gerada |
| DEC-03 | CNO (doc. 17) aceita só PDF |
| DEC-04 | Junção aceita só PDF (não imagens) |
| DEC-05 | 50 MB por arquivo; backup = copiar `storage/` |
| DEC-08 | Acervo fotográfico aceita só `.pptx` (não `.ppt`) |
| — | **Devoluções fora do total de despesas da conciliação**: um projeto que devolveu saldo mostra a conciliação diferente de zero (a tela explica). Vale confirmar com a planilha antiga |
| — | Linha "SALDO" acrescentada ao PDF do balancete; CNPJ validado só pela quantidade de dígitos |

**Outras pendências:**
- **Commit:** nada do trabalho de 2026-11-12 foi para o git ainda.
- **Redesenho visual (fase 3):** Municípios e lista de Projetos feitos; demais telas pausadas. O CSS está em 39,2 KB
  (limite do teste: 40 KB). Antes da próxima tela, decidir entre limpar regras antigas (recomendado) e subir o limite.
- O resumo "N documento(s) no ZIP" conta os registros; um arquivo apagado à mão do disco só é avisado depois do
  download.
- O menu ⋮ dos municípios fecha clicando nele de novo (não ao clicar fora).

## 6. Limitações conhecidas

- Uso local por uma pessoa, sem login; não expor à rede (servidor só aceita `localhost`).
- Os PDFs são "muito próximos" da planilha, não idênticos pixel a pixel (decisão do usuário).
- Ghostscript e LibreOffice são instalados à parte (README).
