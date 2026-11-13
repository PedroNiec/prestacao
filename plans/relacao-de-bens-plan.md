# PLAN — Relação de bens (documento 09) — Etapa 9

**Versão:** 1.0
**Status:** Implementado (2026-11-12) — aguardando conferência do PDF pelo usuário
**SPEC relacionada:** `specs/relacao-de-bens.md` (v1.1, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Um novo relatório "digitado" que segue **exatamente o modelo do físico-financeiro** (doc. 22): tabela própria,
repositório, serviço (com "desatualizado" e geração do PDF), controlador, rotas, telas e template de PDF. Além
disso, a linha 09 da tela do Projeto passa a apontar para o formulário.

## 2. Análise da arquitetura atual

- **Modelo a copiar:** `FisicoFinanceiroRepository` (list, count, find, create, update, delete, move com `ordem`),
  `FisicoFinanceiroService` (`invalidar()` → `DocumentoRepository::markOutdated`, `gerar()` → `ReportPdfService` +
  `FileStorage::storeGenerated` + `DocumentoRepository::save`, apagando o PDF antigo), `FisicoFinanceiroController`
  (validação do projeto, `Session::old` para devolver o formulário com erro, 404 para linha de outro projeto).
- **Configuração por projeto:** a conciliação guarda o título numa tabela `conciliacao` com `projeto_id` como chave.
  O título da relação de bens segue o mesmo padrão (`bens_config`).
- **PDF:** `templates/pdf/_estilo.php`, `_cabecalho.php` e `_rodape.php` são reaproveitados **sem alteração**. O
  rodapé já assina Contador e Prefeito (padrão).
- **Tela do Projeto:** `ProjetoController::mostrar()` monta `$relatorios` (01, 22, 15); basta acrescentar o '09'.
  O `DocumentoLinhaPresenter` já trata formulários prontos de forma genérica: **nenhuma mudança nele**.
- **Catálogo:** o doc. 09 já existe (`REPORT`, arquivo `09-relacao-de-bens.pdf`).
- **Proteção dos PDFs:** `testPdfTemplatesWereNotChangedByTheVisualRefactor` compara hashes. Verifiquei que, com as
  quebras de linha normalizadas (CRLF → LF), os hashes deste computador **batem exatamente** com os registrados.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `database/migrations/007_relacao_bens.sql` | **Criar** | Tabelas `bens` e `bens_config` |
| `src/Repositories/BensRepository.php` | **Criar** | CRUD, ordem, contagem, título |
| `src/Domain/Validator.php` | Alterar (acrescentar) | `Validator::bem()` (descrição, valor > 0) |
| `src/Services/BensService.php` | **Criar** | Regras: invalidar, dados (bens, total, título), gerar PDF em retrato |
| `src/Controllers/BensController.php` | **Criar** | Telas e ações |
| `src/bootstrap.php` | Alterar | Rotas e montagem; `BensRepository` entra no `ProjetoController` |
| `src/Controllers/ProjetoController.php` | Alterar | `$relatorios['09']` (url, "Abrir relação de bens", n, "bem(ns)") |
| `templates/bens/index.php`, `templates/bens/form.php` | **Criar** | Tela e formulário no padrão das telas de documento (trilha, cartão do PDF, estado vazio) |
| `templates/pdf/relacao_bens.php` | **Criar** | PDF em retrato igual à aba da planilha |
| `templates/projetos/show.php` | Alterar | Remover os 2 textos provisórios (DEC-B2) |
| `tests/RelacaoBensTest.php` | **Criar** | Critérios CA-B01 a CA-B08 |
| `tests/TelaProjetoTest.php` | Alterar 1 teste | Hoje exige os textos provisórios (DEC-P3); passa a exigir a ausência deles e o botão novo |
| `tests/DesignSystemTest.php` | Alterar | (a) incluir o hash do PDF novo; (b) **normalizar CRLF → LF** antes do hash (DT-1); (c) aceitar `\` nos caminhos do verificador de classes (DT-2) |
| specs, plano, `docs/HISTORICO.md` | Alterar (no fim) | Registro |

## 4. Banco de dados

Migração **nova** (`007`), sem alterar tabelas existentes:

```sql
CREATE TABLE bens (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id     INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    ordem          INTEGER NOT NULL,
    descricao      TEXT NOT NULL,
    valor_centavos INTEGER NOT NULL CHECK (valor_centavos > 0)
);
CREATE INDEX idx_bens_projeto ON bens(projeto_id, ordem);

CREATE TABLE bens_config (
    projeto_id INTEGER PRIMARY KEY REFERENCES projetos(id) ON DELETE CASCADE,
    titulo     TEXT NOT NULL DEFAULT ''
);
```

- O título vazio (ou sem registro) significa o padrão "RELAÇÃO DE BENS ADQUIRIDOS" (EX-B03).
- `ON DELETE CASCADE`: excluir o projeto apaga os bens (o PDF gerado já é apagado pelo fluxo atual de exclusão).
- **A migração roda sozinha** ao iniciar (como as anteriores), inclusive no seu banco real; ela só **cria** tabelas.

## 5. Serviços e regras de negócio

| Responsabilidade | Onde |
|---|---|
| Validar descrição (obrigatória, ≤ 500) e valor (> 0, formato "1.234,56") | `Validator::bem()` (usa `Money::parse`, como os outros) |
| Validar título (≤ 200; vazio = padrão) | `BensController` |
| Total = soma dos valores (RN-B02) | `BensService::dados()` |
| Marcar o doc. 09 "desatualizado" ao criar, editar, excluir, mover ou mudar o título (RF-B08) | `BensService::invalidar()` |
| Gerar o PDF (retrato) e substituir o anterior | `BensService::gerar()` |

## 6. Fluxo técnico

**Rotas** (base `/projetos/{id}/relacao-de-bens`):

| Método | Caminho | Ação |
|---|---|---|
| GET | (base) | tela da relação de bens |
| POST | `/gerar` | gerar PDF |
| POST | `/titulo` | salvar título |
| GET | `/bens/novo` | formulário (1º bem: descrição = objeto do projeto, DEC-B1) |
| POST | `/bens` | criar |
| GET | `/bens/{bid}/editar` | formulário de edição |
| POST | `/bens/{bid}` | atualizar |
| POST | `/bens/{bid}/excluir` | excluir |
| POST | `/bens/{bid}/mover` | ↑ ↓ |

**PDF** (`pdf/relacao_bens.php`), na ordem da planilha:
1. `_estilo` e `_cabecalho` (Executor, Concedente, Processo, PP/NE/NL, Objeto);
2. título em destaque;
3. tabela sem cabeçalho de colunas: Item | Descrição | Valor (R$);
4. linha **TOTAL**;
5. `_rodape` (local, data por extenso, Contador e Prefeito).

Retrato. Com a lista vazia: "Nenhum bem cadastrado." e TOTAL 0,00.

**Tela** (`bens/index.php`), no mesmo padrão do físico-financeiro:
- trilha "Projetos › objeto › 09 Relação de bens";
- cabeçalho com "Novo bem";
- cartão do PDF (`partes/pdf`, textos "PDF não gerado", "PDF gerado" e "Os bens ou o título mudaram desde a última
  geração.");
- tabela Item · Descrição · Valor · ações (Editar, ↑, ↓, Excluir) com o TOTAL no rodapé;
- cartão "Título do documento" com o campo e "Salvar título";
- estado vazio com "Novo bem".

Só classes CSS que já existem (o CSS está em 39,2 KB; nada novo previsto).

## 7. Segurança e permissões

- CSRF em todos os POST; `confirm('Excluir este bem?')`.
- Bem de outro projeto: `find(projetoId, id)` com os dois ids → 404 (EX-B04), como no físico-financeiro.
- Tudo escapado com `e()`, também no template do PDF.
- Consultas preparadas (PDO), como no restante.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Não dá para ler o texto do PDF neste computador (sem Ghostscript) | Erro de layout no PDF passar despercebido | Testar o **HTML** que gera o PDF (o mesmo que o Dompdf recebe) e que o PDF gerado é válido (`%PDF`, 1 página, retrato); você confere o PDF baixado (CA-B09). |
| Migração no banco real | Alterar dados existentes | Só **cria** tabelas novas; o sistema de migrações registra a versão aplicada. |
| Mudar o `DesignSystemTest` | Enfraquecer a proteção dos PDFs | A normalização mantém os mesmos hashes (verificado); só deixa o teste funcionar também no Windows. |
| Linha 09 da tela do Projeto mudar de "indisponível" para "formulário" | Testes da tela | `TelaProjetoTest` ajustado (registrado); os textos da DEC-P3 deixam de existir por decisão (DEC-B2). |

## 9. Estratégia de implementação

- [x] **Etapa 1: dados.** Migração, `BensRepository`, `Validator::bem()` e testes de repositório e validação.
- [x] **Etapa 2: serviço e PDF.** `BensService`, `pdf/relacao_bens.php`; ajustes do `DesignSystemTest` (DT-1, DT-2 e hash).
- [x] **Etapa 3: telas e rotas.** Controlador, rotas, `bens/index.php` e `bens/form.php`.
- [x] **Etapa 4: tela do Projeto.** `$relatorios['09']`, remoção dos textos provisórios, ajuste do `TelaProjetoTest`.
- [~] **Etapa 5: validação.** *(automática e PDF real feitos; falta a sua conferência)* Suíte completa sem falha nova (conferindo o conteúdo das falhas), fluxo no servidor de
      demonstração, PDF gerado de verdade para você conferir, documentos atualizados.

## 10. Testes e validações

`RelacaoBensTest`:
- [ ] CA-B01: criar 3 bens, editar, mover (a numeração muda), excluir com confirmação.
- [ ] CA-B02: o formulário do 1º bem traz a descrição = objeto; o do 2º vem vazio.
- [ ] CA-B03: o HTML do PDF tem cabeçalho, título, "1", "2", descrições, valores, "TOTAL" com a soma, local/data e as
      duas assinaturas; o PDF gerado começa com `%PDF` e o documento 09 fica registrado como gerado.
- [ ] CA-B04: título salvo aparece na tela e no PDF; vazio volta ao padrão.
- [ ] CA-B05: a tela do Projeto mostra "Abrir relação de bens", "N bem(ns)" e a situação; os textos provisórios sumiram.
- [ ] CA-B06: depois de gerar, criar, editar, excluir, mover ou mudar o título → "Desatualizado".
- [ ] CA-B07: valor vazio, zero, negativo e inválido; descrição vazia; bem de outro projeto → 404; título com mais de
      200 caracteres.
- [ ] Segurança: `<script>` na descrição sai escapado na tela e no HTML do PDF; POST sem CSRF é recusado.
- [ ] Exclusão do projeto apaga os bens (cascade).

Regressão: suíte completa comparada com a lista de referência, **conferindo o conteúdo** das falhas.

## 11. Critérios de conclusão

CA-B01 a CA-B08 verificados; CA-B09 com você; spec principal, spec, plano e histórico atualizados.

## 12. Decisões técnicas pendentes

- [x] DT-1 **Aprovada.** **Normalizar quebras de linha no teste de hash dos PDFs.** *Recomendo aprovar:* é preciso de qualquer
      forma para registrar o hash do PDF novo de modo estável, e corrige uma falha que só existe no Windows (os hashes
      atuais continuam válidos, verificado).
- [x] DT-2 **Aprovada.** **Aceitar `\` nos caminhos do verificador de classes.** *Recomendo aprovar:* hoje esse teste falha no Windows
      e, por isso, **escondeu um defeito real** na fase 3.1. É uma linha (`str_replace('\\', '/', …)`).

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12).
