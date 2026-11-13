# PLAN — ZIP da prestação de contas — Etapa 10

**Versão:** 1.0
**Status:** Implementado (2026-11-12) — aguardando a conferência do usuário
**SPEC relacionada:** `specs/zip-prestacao.md` (v1.1, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Um serviço monta o ZIP num arquivo temporário a partir dos documentos registrados do projeto. Uma rota `GET` o
envia e a `Response` apaga o temporário depois do envio. A tela do Projeto ganha o botão, o resumo de pendências e a
confirmação. Sem banco novo e sem dependências.

## 2. Análise da arquitetura atual

- `DocumentoRepository::byProjeto()` traz os documentos registrados (com `caminho`, `desatualizado`).
- `FileStorage::exists()` e `absolute()` resolvem os caminhos relativos a `storage/`. O download individual usa o
  mesmo par (`DocumentoService::arquivo`).
- `DocumentCatalog::all()` dá a ordem e o nome de cada arquivo (`arquivo` = `01-balancete.pdf`…).
- `Response::file()` + `send()` fazem `readfile()`; **não há como apagar o arquivo depois**. É preciso uma opção
  nova.
- `DocumentoLinhaPresenter` já calcula, por linha, `desatualizado` e a situação ("Não gerado"); o resumo de pendências
  sai dessas linhas, sem consulta nova.
- `Session::flash()` guarda mensagens para a próxima página; serve para o aviso do EX-Z02 depois do download.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `src/Services/ZipService.php` | **Criar** | Monta o ZIP (ordem e nomes do catálogo, sem recompressão), nome do arquivo com o município, lista de ausentes |
| `src/Controllers/ZipController.php` | **Criar** | `GET /projetos/{id}/zip`: valida, monta e envia; trata EX-Z01 a EX-Z04 |
| `src/Core/Response.php` | Alterar | Parâmetro opcional `apagarDepois` (padrão `false`) e `Response::tempFile()`; `send()` apaga o arquivo depois do `readfile()`. Nada muda para quem já usa `file()`. |
| `src/Presenters/DocumentoLinhaPresenter.php` | Alterar (acrescentar) | `resumoZip(array $linhas)` → quantos têm PDF, códigos desatualizados e códigos não gerados |
| `src/Controllers/ProjetoController.php` | Alterar | Passa o resumo ao template |
| `templates/projetos/show.php` | Alterar | Botão no cabeçalho, resumo das pendências e a confirmação (`onclick="return confirm(...)"`) |
| `src/bootstrap.php` | Alterar | Rota e montagem |
| `tests/ZipTest.php` | **Criar** | CA-Z01 a CA-Z06 |
| `tests/DocumentoLinhaPresenterTest.php` | Alterar (acrescentar) | Casos do `resumoZip` |
| specs, planos, `docs/HISTORICO.md` | Alterar (no fim) | Registro |

## 4. Banco de dados

**Nenhuma alteração.**

## 5. Serviços e regras de negócio

| Responsabilidade | Onde |
|---|---|
| Quais documentos entram, em que ordem e com que nome (RN-Z01) | `ZipService::montar()` (percorre o catálogo; usa o registro do banco e confere se o arquivo existe) |
| Ignorar e listar os arquivos ausentes do disco (RN-Z03 / EX-Z02) | `ZipService::montar()` → `ausentes` |
| Nome do ZIP (RF-Z03) | `ZipService::nomeArquivo()` (município sem acentos, minúsculo, com hífens) |
| Desatualizados entram; só se avisa (RN-Z02) | Nenhuma regra no serviço; o aviso fica na tela |
| Resumo de pendências para a tela (RF-Z04) | `DocumentoLinhaPresenter::resumoZip()` |

## 6. Fluxo técnico

```
Tela do Projeto ── GET /projetos/{id}/zip ──► ZipController::baixar()
   ├─ projeto não existe → 404 (EX-Z04)
   ├─ ZipService::montar(id) → ['caminho' => tmp, 'nome' => …zip, 'incluidos' => n, 'ausentes' => [códigos]]
   │     • percorre DocumentCatalog::all() (ordem da prestação)
   │     • para cada registro de byProjeto(): se o arquivo existe → ZipArchive::addFile(abs, nome do catálogo) com
   │       compressão "store" (PDF já é comprimido; mais rápido e o conteúdo não muda)
   │     • zip em sys_get_temp_dir() (fora de storage/)
   ├─ 0 incluídos → apaga o tmp, flash "Não há documentos para baixar", redirect ao projeto (EX-Z01)
   ├─ falha do ZipArchive → flash de erro, redirect (EX-Z03)
   ├─ ausentes → flash "01 não foi incluído: arquivo não encontrado" (aparece na próxima página)
   └─ Response::tempFile(tmp, nome, 'application/zip') → send(): readfile + unlink (CA-Z05)
```

**Tela (cabeçalho do Projeto):**

```html
<div class="acoes">
  <a class="btn secundario" href="/projetos/1/editar">Editar dados</a>
  <a class="btn" href="/projetos/1/zip" onclick="return confirm('2 documento(s) desatualizado(s): 01, 15.\n1 documento(s) não gerado(s): 03 (não entra no ZIP).\n\nBaixar mesmo assim?')">Baixar prestação (ZIP)</a>
</div>
<p class="dica">9 documentos no ZIP · <span class="tag aviso-tag">2 desatualizados</span> (01, 15) · <span class="tag aviso-tag">1 não gerado</span> (03)</p>
```

- Sem pendências: sem `onclick` e sem as etiquetas.
- Sem nenhum PDF: `<span class="btn" aria-disabled="true">` com a dica "Nenhum documento com PDF ainda" (o CSS já tem
  o estilo `[aria-disabled="true"]`).
- O texto do `confirm()` é escapado para JavaScript **e** para HTML (`json_encode` + `e()`), sem risco de injeção.

## 7. Segurança e permissões

- Só arquivos **registrados no banco** para o projeto entram (nunca um caminho da requisição); o caminho absoluto vem
  de `FileStorage::absolute()`, que já impede sair de `storage/`.
- Nome do ZIP e dos arquivos internos gerados pelo sistema (catálogo e município sem acentos); `Response::file` já
  sanitiza o cabeçalho `Content-Disposition`.
- `GET` não altera dados, então não precisa de CSRF (como o download individual).
- O temporário é apagado depois do envio; em teste (sem `send()`), o teste apaga.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Alterar `Response` | Quebrar downloads existentes | Parâmetro novo opcional com padrão `false`; teste dos downloads atuais segue igual. |
| Temporário não apagado se o processo cair no meio | Lixo em `%TEMP%` | Arquivos em `sys_get_temp_dir()` com prefixo próprio; o sistema operacional limpa. Volume pequeno. |
| Projeto grande (muitos PDFs grandes) | Demora para montar | Sem recompressão ("store"); limite de 50 MB por arquivo já existe. |
| Aviso de pendências não ser visto (JS desligado) | Baixar sem saber das pendências | O resumo também aparece **em texto** ao lado do botão. |

## 9. Estratégia de implementação

- [x] **Etapa 1:** `Response` (apagar depois), `ZipService` e testes do serviço.
- [x] **Etapa 2:** `ZipController`, rota e testes HTTP.
- [x] **Etapa 3:** *(o resumo é calculado no template a partir das linhas; o `ProjetoController` não precisou mudar)* `resumoZip` no apresentador, botão e aviso na tela do Projeto, testes.
- [~] **Etapa 4:** validação. *(automática e ZIP real feitos; falta abrir no Windows)* Suíte completa, ZIP real no servidor de demonstração (listar o conteúdo, comparar os
      hashes com os originais), documentos atualizados; você abre o ZIP no Windows (CA-Z07).

## 10. Testes e validações

`ZipTest`:
- [ ] CA-Z01: projeto com 3 documentos (enviado, gerado e desatualizado) e 1 "não gerado": o ZIP tem exatamente os 3,
      com os nomes do catálogo, na ordem; o conteúdo de cada um é igual (hash) ao original.
- [ ] CA-Z02: `Content-Disposition` com `prestacao-de-contas-caxambu-do-sul-projeto-N.zip`; município com acentos e
      espaços vira minúsculo com hífens.
- [ ] CA-Z04: sem nenhum PDF → redirect ao projeto com "Não há documentos para baixar".
- [ ] EX-Z02: arquivo registrado, mas apagado do disco → fica de fora e gera o aviso; os outros entram.
- [ ] CA-Z05: `Response::tempFile` → depois de `send()` o arquivo não existe mais.
- [ ] EX-Z04: projeto inexistente → 404.
- [ ] CA-Z03 (tela): com pendências, o botão tem `confirm` com os códigos e a dica com as etiquetas; sem pendências, sem
      `confirm`; sem PDF, botão desabilitado.
- [ ] `DocumentoLinhaPresenterTest`: `resumoZip` conta PDFs, desatualizados e não gerados corretamente.

Regressão: suíte completa comparada com a referência, conferindo o conteúdo das falhas.

## 11. Critérios de conclusão

CA-Z01 a CA-Z06 verificados; CA-Z07 com você; spec principal (RF-21, EX-05, CA-09, DEC-06), plano principal (Etapa 10)
e histórico atualizados.

## 12. Decisões técnicas pendentes

Nenhuma.

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12).
