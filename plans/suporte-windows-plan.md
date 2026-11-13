# PLAN — Suporte ao Windows (Ghostscript e LibreOffice)

**Versão:** 1.0
**Status:** Concluído (2026-11-12)
**SPEC relacionada:** `specs/suporte-windows.md` (v1.1, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Uma classe pequena, `Ferramentas`, decide **qual executável** usar (Ghostscript e LibreOffice) e monta a **URL de
arquivo** do perfil do LibreOffice. `PdfMergeService`, `ConversionService` e `tests/Support.php` passam a usá-la. Os
testes deixam de usar a linha de comando do shell (`exec` com `>/dev/null`) e passam a usar `proc_open` com lista de
argumentos, que funciona igual no Windows e no Linux. O README ganha a seção Windows.

## 2. Análise da arquitetura atual

- `PdfMergeService::__construct`: `$gs ??= getenv('PRESTACAO_GS') ?: 'gs'`. Os comandos rodam via `proc_open` com
  lista (sem shell), o que já é portável. A contagem de páginas passa o caminho em `--permit-file-read=` e
  `-sPDFname=`; **no Windows os `\` precisam ser conferidos** (o Ghostscript aceita `/`).
- `ConversionService::__construct`: `$soffice ??= getenv('PRESTACAO_SOFFICE') ?: 'soffice'`. Perfil:
  `-env:UserInstallation=file://` + `$tmp` (em Linux vira `file:///tmp/...`; no Windows ficaria `file://C:\...`,
  inválido). A mensagem de "não instalado" cita `apt`.
- `ConversionService::pngToPdf` usa o Dompdf e depois confere as páginas pelo `PdfMergeService` (Ghostscript).
- `tests/Support.php`: `exec()` com `gs`/`soffice` fixos, `>/dev/null` e `file://`.
- Os testes de conversão de PowerPoint chamam `Support::soffice()` e são **pulados** se ele não existir.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `src/Services/Ferramentas.php` | **Criar** | `ghostscript()`, `libreOffice()`, `urlArquivo()`; sistema operacional e verificação de arquivo **injetáveis** para teste (CA-W04) |
| `src/Services/PdfMergeService.php` | Alterar | Usar `Ferramentas::ghostscript()`; caminhos com `/` nos argumentos do Ghostscript; mensagem de "não instalado" com instrução de Windows e Linux (RF-W06) |
| `src/Services/ConversionService.php` | Alterar | Usar `Ferramentas::libreOffice()` e `Ferramentas::urlArquivo()`; mensagem de "não instalado" com instrução de Windows e Linux |
| `tests/Support.php` | Alterar | Usar `Ferramentas`; executar com `proc_open` (lista de argumentos), sem `/dev/null` |
| `tests/FerramentasTest.php` | **Criar** | Ordem de busca em Windows e Linux simulados, variáveis de ambiente, URL de arquivo |
| `README.md` | Alterar | Seção "Windows" (RF-W05) |
| specs, planos, `docs/HISTORICO.md` | Alterar (no fim) | Registro e conclusão da Etapa 11 |

## 4. Banco de dados

**Nenhuma alteração.**

## 5. Serviços e regras de negócio

Nenhuma regra muda. A ordem de busca:

| Ferramenta | 1º | 2º (só Windows) | 3º | Último (igual ao de hoje) |
|---|---|---|---|---|
| Ghostscript | `PRESTACAO_GS` | `gswin64c` no PATH | `C:\Program Files\gs\gs*\bin\gswin64c.exe` (versão mais recente) | `gs` |
| LibreOffice | `PRESTACAO_SOFFICE` | `C:\Program Files\LibreOffice\program\soffice.exe` | — | `soffice` |

`urlArquivo('C:\Users\x\perfil')` → `file:///C:/Users/x/perfil`; `urlArquivo('/tmp/x')` → `file:///tmp/x`.

## 6. Fluxo técnico

Nada muda no fluxo do usuário. Internamente, o construtor de cada serviço passa a chamar `Ferramentas::…()` quando
nenhum executável é informado (os testes continuam podendo informar um executável inexistente para simular "não
instalado").

## 7. Segurança e permissões

- Continua **sem shell**: `proc_open` com lista de argumentos; nenhum texto do usuário vira comando.
- Os caminhos procurados são fixos (pastas padrão do `winget`) ou vêm de variáveis de ambiente do próprio usuário.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Quebrar o Linux | Junção e conversão param no computador antigo | O último recurso continua `gs`/`soffice`; teste com sistema operacional simulado (CA-W04). |
| Ghostscript do Windows não aceitar `\` nos argumentos | Contagem de páginas falha e a junção recusa tudo | Normalizar para `/`; confirmar com os testes reais de junção (CA-W01). |
| Ferramentas não instaladas quando eu for testar | Não dá para comprovar CA-W01/W02 | Você instala com `winget` antes; se não der, entrego a parte de código com os testes simulados e deixo CA-W01/W02 pendentes. |
| Primeira execução do LibreOffice lenta (cria o perfil) | Testes de PowerPoint lentos | Já há timeout de 180 s; aceitável. |
| Diferenças de saída do Ghostscript no Windows (`\r\n`) | Contagem de páginas não reconhecida | O código já faz `trim` por linha; confirmado pelos testes reais. |

## 9. Estratégia de implementação

- [x] **Etapa 1:** `Ferramentas` e `FerramentasTest` (funciona sem as ferramentas instaladas).
- [x] **Etapa 2:** serviços (`PdfMergeService`, `ConversionService`) e mensagens.
- [x] **Etapa 3:** `tests/Support.php`.
- [x] **Etapa 4:** com Ghostscript e LibreOffice instalados: suíte completa (meta: tudo passando aqui, sem pulados),
      junção, PNG e PowerPoint no servidor de demonstração.
- [x] **Etapa 5:** README; **concluir a Etapa 11** (relatório de validação final com CA-01 a CA-09) e atualizar o
      histórico.

## 10. Testes e validações

- [ ] `FerramentasTest`: variável de ambiente tem prioridade; Windows encontra `gswin64c` no PATH; Windows escolhe a
      versão mais recente em `Program Files\gs`; Linux devolve `gs`/`soffice`; LibreOffice no Windows na pasta padrão;
      `urlArquivo` nos dois formatos.
- [ ] Suíte completa neste Windows, com as ferramentas instaladas: **0 falhas e 0 pulados** (CA-W01).
- [ ] Manual no servidor de demonstração: juntar 2 PDFs, enviar PNG e enviar PPTX (CA-W02).

## 11. Critérios de conclusão

CA-W01 a CA-W06; Etapa 11 concluída com o relatório final.

## 12. Decisões técnicas pendentes

Nenhuma.

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12).

## 14. Registro

- Fora do plano, aprovado pelo usuário durante a Etapa 4: validação de `.pptx` no `ConversionService` (o LibreOffice 26.x converte qualquer arquivo) e teste `testZipThatIsNotAPresentationIsRejectedBeforeLibreOffice`.
- O ID `ArtifexSoftware.GhostScript` não existe no `winget`; README e mensagem de erro apontam o instalador oficial.
