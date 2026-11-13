# SPEC — Suporte ao Windows (Ghostscript e LibreOffice)

**Versão:** 1.2
**Status:** Concluída (2026-11-12)
**Data:** 2026-11-12
**Relacionada a:** `specs/prestacao-de-contas.md` (RF-06 a RF-12, CA-03, CA-05) e à Etapa 11 (validação final) de
`plans/prestacao-de-contas-plan.md`.

---

## 1. Contexto

O sistema foi desenvolvido e validado num computador **Linux**. O usuário passou a usá-lo num computador **Windows 11**
e vai continuar usando nele (decisão de 2026-11-12). A validação final (Etapa 11) mostrou que, neste Windows:

| Funcionalidade | Situação | Causa |
|---|---|---|
| Junção de PDFs (docs 02–07, 13, 14) | **Não funciona** | Ghostscript não instalado; e no Windows o programa se chama `gswin64c.exe`, não `gs` |
| Conversão de PowerPoint (doc 08) | **Não funciona** | LibreOffice não instalado; no Windows o `soffice.exe` não fica no PATH; o perfil temporário usa `file://` + caminho, formato de Linux |
| Conversão de PNG (doc 16) | **Não funciona** | A conferência de páginas do PDF gerado usa o Ghostscript |
| 48 testes | Falham | Mesmas causas, e `tests/Support.php` usa `gs`, `soffice`, `>/dev/null` e `file://` fixos |
| README | Só Linux | `sudo apt …` |

Todo o resto (cadastros, uploads, formulários, PDFs dos relatórios via Dompdf, ZIP) funciona no Windows.

## 2. Objetivo

O sistema funcionar **por completo** neste Windows, com os 314 testes rodando aqui, **sem quebrar o Linux**.

## 3. Usuários envolvidos

O único usuário, agora num Windows 11.

## 4. Requisitos funcionais

- **RF-W01 Localizar o Ghostscript automaticamente**, nesta ordem: variável `PRESTACAO_GS` (como hoje) → no Windows,
  `gswin64c` no PATH ou na pasta padrão `C:\Program Files\gs\gs*\bin\gswin64c.exe` (a versão mais recente) → `gs`
  (Linux, como hoje).
- **RF-W02 Localizar o LibreOffice automaticamente**: `PRESTACAO_SOFFICE` (como hoje) → no Windows,
  `C:\Program Files\LibreOffice\program\soffice.exe` → `soffice` (Linux, como hoje).
- **RF-W03 Perfil temporário do LibreOffice** com uma URL de arquivo válida nos dois sistemas (`file:///C:/…` no
  Windows; `file:///tmp/…` no Linux).
- **RF-W04 Testes** usam a mesma localização (RF-W01 e RF-W02) e nenhum comando exclusivo do Linux (`/dev/null`).
- **RF-W05 README** com uma seção de instalação no Windows: PHP, Composer, `winget` do Ghostscript e do LibreOffice e
  uma porta alternativa caso a 8000 esteja ocupada.
- **RF-W06 Mensagem clara** quando a ferramenta não for encontrada (hoje já existe "LibreOffice não está
  instalado…"); acrescentar o equivalente para o Ghostscript, citando onde instalar.

## 5. Requisitos não funcionais

- **Linux inalterado:** a ordem de busca termina em `gs` e `soffice`, exatamente como hoje.
- Sem dependências novas. As ferramentas externas são instaladas pelo usuário (`winget`); o sistema só as encontra.
- Comandos externos continuam com argumentos separados (`proc_open` com lista), sem montar linha de comando com texto
  do usuário.

## 6. Regras de negócio

Nenhuma alterada.

## 7. Fluxos de utilização

1. O usuário instala o LibreOffice com `winget` e o Ghostscript pelo instalador oficial (o Ghostscript **não está no
   catálogo do `winget`**, constatado em 2026-11-12). Instruções no README.
2. Inicia o sistema normalmente. Junção, PowerPoint e PNG passam a funcionar, sem configurar nada.
3. (Opcional) Se instalou em outra pasta, define `PRESTACAO_GS` ou `PRESTACAO_SOFFICE`.

## 8. Exceções e erros

- EX-W01 Ferramenta não encontrada → mensagem dizendo qual instalar e como (RF-W06); nenhum arquivo é alterado.
- EX-W02 Várias versões do Ghostscript instaladas → usa a mais recente.

## 9. Critérios de aceitação

- [x] CA-W01 Com Ghostscript e LibreOffice instalados por `winget`, **todos os 314 testes passam neste Windows**
      (exceto os explicitamente condicionais, que devem rodar e não ser pulados).
- [x] CA-W02 No servidor de demonstração: juntar PDFs, converter PNG e converter PowerPoint funcionam.
- [x] CA-W03 A localização respeita `PRESTACAO_GS` e `PRESTACAO_SOFFICE` (teste).
- [x] CA-W04 No Linux, a ordem de busca termina em `gs` e `soffice` (teste da função de localização com o sistema
      operacional simulado).
- [x] CA-W05 README com a seção Windows.
- [x] CA-W06 Depois disso, a Etapa 11 é concluída aqui: CA-03, CA-05, CA-06 e CA-08 da spec principal reverificados.

## 10. Restrições técnicas

- Arquivos previstos: `src/Services/PdfMergeService.php`, `src/Services/ConversionService.php`, uma classe pequena
  de localização das ferramentas, `tests/Support.php`, `README.md`.
- Não é possível testar o Linux neste computador: a compatibilidade é garantida por manter `gs` e `soffice` como
  último recurso e por um teste com o sistema operacional simulado.

## 11. Fora do escopo

- Instalar as ferramentas pelo sistema (o usuário instala com `winget`).
- Empacotar o sistema como instalador do Windows ou serviço que inicia sozinho.
- macOS.

## 12. Decisões pendentes

- [x] DEC-W1 **Aprovada.** **Busca automática nas pastas padrão do Windows** (RF-W01 e RF-W02), em vez de exigir as variáveis de
      ambiente. *Recomendo aprovar:* funciona logo após o `winget`, sem configuração. *Padrão: aprovar.*

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação, a partir das descobertas da validação final no Windows | 2026-11-12 |
| 1.1 | Aprovada pelo usuário com a recomendação (DEC-W1) | 2026-11-12 |
| 1.2 | **Implementada.** 322/322 testes neste Windows (Ghostscript 10.08.0, LibreOffice 26.8.3), nenhum pulado; junção, PNG e PPTX conferidos no servidor real. O Ghostscript não está no `winget` (README usa o instalador oficial). Achado: o LibreOffice 26.x converte arquivos que não são apresentações; o conversor passou a conferir `ppt/presentation.xml` antes (aprovado pelo usuário) | 2026-11-12 |
