# PLAN — Conciliação bancária: receitas independentes do balancete

**Versão:** 1.0
**Status:** Implementado (2026-11-13)
**SPEC relacionada:** `specs/conciliacao-receitas-independentes.md` (v1.0, aprovada em 2026-11-13, com as DEC-01, DEC-02 e DEC-03 recomendadas)
**Data:** 2026-11-13

---

## 1. Resumo da implementação

Troca o cálculo "receitas a partir do balancete + sobrescritas" por uma **lista própria de receitas da conciliação**
(tabela nova), com tela de lista e formulário de adicionar/editar no mesmo padrão das **notas fiscais da conciliação**.
Remove tudo que ligava a conciliação ao balancete: cálculo, sobrescritas (`overrides`), rota "restaurar" e a invalidação
do documento 15 quando o balancete muda. O PDF passa a mostrar "Tipo - descrição" e trata a lista vazia.

## 2. Análise da arquitetura atual

- `ConciliacaoCalculator::receitasCalculadas()` monta 7+ linhas a partir de `lancamentos`; `aplicarOverrides()` aplica as sobrescritas.
- `ConciliacaoService::dados()` junta os dois e entrega `receitas` (com `chave`, `rotulo`, `calculado`, `valor`, `alterado`) à tela e ao PDF.
- `ConciliacaoService::salvarReceitas()` e `restaurar()` gravam/removem linhas em `overrides` (via `OverrideRepository`).
- As notas fiscais já têm o padrão completo que será copiado: tabela `conciliacao_notas`, métodos no `ConciliacaoRepository` (criar, atualizar, excluir, mover), validador, controller, rotas e `nota_form.php`.
- `BalanceteService::DEPENDENTES = ['01','09','15']` marca esses documentos como desatualizados a cada mudança no balancete.
- `ConciliacaoCalculator::totais()` recebe as receitas já com a chave `valor` e não precisa mudar.
- `overrides` só é usada pela conciliação (confirmado por busca no código).

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `database/migrations/008_conciliacao_receitas.sql` | Criar | Tabela `conciliacao_receitas`; remove `overrides` (DEC-01) |
| `src/Repositories/ConciliacaoRepository.php` | Alterar | CRUD, ordem e `mover` das receitas (cópia do padrão das notas) |
| `src/Repositories/OverrideRepository.php` | **Excluir** | Fica sem uso (DEC-01) |
| `src/Domain/ConciliacaoCalculator.php` | Alterar | Remove `receitasCalculadas`, `aplicarOverrides`, `MIN_SLOTS`; adiciona `TIPOS` e `rotuloReceita()`; atualiza o comentário da classe |
| `src/Domain/Validator.php` | Alterar | `conciliacaoReceita()`: tipo, descrição (200), valor > 0 (DEC-02, DEC-03) |
| `src/Services/ConciliacaoService.php` | Alterar | Lê receitas do repositório; remove `salvarReceitas`/`restaurar`; adiciona criar/atualizar/excluir/mover (invalidam o doc 15); sai a dependência de `OverrideRepository` e `LancamentoRepository` |
| `src/Controllers/ConciliacaoController.php` | Alterar | Troca `salvarReceitas`/`restaurar` por `receitaNova/Criar/Editar/Atualizar/Excluir/Mover` |
| `src/bootstrap.php` | Alterar | Novas rotas; remove `receitas/restaurar`; ajusta o construtor do serviço |
| `templates/conciliacao/index.php` | Alterar | Seção 1 vira lista no padrão das notas; texto "ou o balancete" sai do aviso de PDF desatualizado |
| `templates/conciliacao/receita_form.php` | Criar | Formulário (tipo, descrição, valor), usado para criar e editar |
| `templates/pdf/conciliacao.php` | Alterar | Linha "Nenhuma receita informada." e total sem intervalo "1.1 até 1.0" quando vazio |
| `src/Services/BalanceteService.php` | Alterar | `DEPENDENTES` passa a `['01','09']` |
| `tests/ConciliacaoTest.php` | Alterar | Remove testes de cálculo a partir do balancete e de sobrescritas; adapta os que usam `receita()` do balancete; novos testes das receitas |
| `tests/BalanceteFluxoTest.php` | Alterar | Esperado passa de `['01','09','15']` para `['01','09']`, com a conciliação **não** afetada |
| `tests/DesignSystemTest.php` | Alterar | Hash de `pdf/conciliacao.php` atualizado de propósito |
| `docs/HISTORICO.md`, `docs/VALIDACAO-FINAL.md`, `specs/prestacao-de-contas.md` | Alterar | Documentação sincronizada (decisão da Etapa 8 substituída; CA-07 da validação final) |

Não há mudança em CSS nem em JS: a lista reutiliza `.secao-cab`, `.rolagem`, `.acoes`, `.estado-vazio` e `.btn`
(o CSS tem 45 bytes de folga e não será tocado).

## 4. Banco de dados

Migração `008_conciliacao_receitas.sql`:

```sql
CREATE TABLE conciliacao_receitas (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id     INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    ordem          INTEGER NOT NULL,
    tipo           TEXT NOT NULL CHECK (tipo IN ('repasse', 'contrapartida', 'rendimentos')),
    descricao      TEXT NOT NULL DEFAULT '',
    valor_centavos INTEGER NOT NULL CHECK (valor_centavos > 0)
);
CREATE INDEX idx_conc_receitas_projeto ON conciliacao_receitas(projeto_id, ordem);
DROP TABLE overrides;
```

- Nenhuma conversão de dados (receitas atuais são de teste, decisão do usuário).
- **Atenção:** o servidor aplica migrações sozinho ao iniciar/atender. Assim que o arquivo existir, a próxima requisição ao servidor em execução já altera o **banco real** (`storage/database.sqlite`). O `DROP TABLE overrides` não tem volta. Mitigação: **backup do banco antes de criar a migração** (Etapa 0).

## 5. Serviços e regras de negócio

- **Validação** (`Validator::conciliacaoReceita`): tipo ∈ {repasse, contrapartida, rendimentos}; descrição opcional, até 200 caracteres; valor via `Money::parse`, obrigatório e > 0.
- **Rótulo** (`ConciliacaoCalculator::rotuloReceita`): `"Tipo - descrição"`; sem descrição, só `"Tipo"`.
- **Serviço**: `dados()` lê as receitas, acrescenta `rotulo` e `valor` (para o PDF e `totais()` continuarem iguais) e as devolve em `receitas`.
- **Invalidação**: criar, atualizar, excluir e mover receita chamam `invalidar()` (doc 15 desatualizado), como nas notas.
- **Balancete**: `DEPENDENTES` perde o `'15'`. O `'09'` (relação de bens) **não** é tocado, pois está fora do escopo.

## 6. Fluxo técnico

`GET /projetos/{id}/conciliacao` → serviço lê receitas, notas e config → tela com a lista.
`GET .../receitas/novo` → `receita_form`. `POST .../receitas` → valida → grava → invalida doc 15 → redireciona para `#receitas`.
`GET .../receitas/{rid}/editar` e `POST .../receitas/{rid}` → idem, com checagem de que a receita pertence ao projeto.
`POST .../receitas/{rid}/excluir` e `.../mover` → idem às notas. `POST .../gerar` → o PDF usa a mesma lista da tela.

Rotas novas (sob `/projetos/{id}/conciliacao`): `receitas/novo` (GET), `receitas` (POST), `receitas/{rid}/editar` (GET),
`receitas/{rid}` (POST), `receitas/{rid}/excluir` (POST), `receitas/{rid}/mover` (POST). Removida: `receitas/restaurar`.
A antiga `POST .../receitas` (salvar em lote) passa a significar "criar uma receita".

## 7. Segurança e permissões

- Todo POST com CSRF (já garantido pelo roteador); texto dinâmico com `e()`; valores em centavos.
- Receita sempre buscada por `id` **e** `projeto_id` (EX-03); `tipo` validado contra lista fechada e também por `CHECK` no banco.
- Consultas com parâmetros preparados; sem SQL montado a partir de entrada do usuário.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Migração roda no banco real assim que o arquivo existe e apaga `overrides` | Perda das sobrescritas (dados de teste) | Backup do banco antes (Etapa 0); usuário já autorizou descartar |
| Rota `POST .../receitas` mudou de significado | Formulário antigo em cache do navegador enviaria `valores[]` e seria rejeitado | Validador recusa e mostra erro; sem impacto em dados |
| Muitos testes de conciliação dependem de `receita()` no balancete | Falhas em cascata na suíte | Reescrever o helper para postar na nova rota; comparar a suíte com a lista de falhas esperadas |
| Hash do PDF da conciliação muda | `DesignSystemTest` falha | Atualizar o hash de propósito, depois de conferir o PDF |
| PDF com lista vazia | Rótulo "1.1 até 1.0" | Tratar o caso no template (EX do plano; coberto por teste) |
| ZIP e documento 15 já gerado | PDF antigo mostra receitas do balancete | Mudar receitas marca o doc 15 como desatualizado; PDFs antigos só são recalculados ao gerar de novo |
| Esquecer referência ao balancete na tela | Viola CA-06 | Busca por "balancete" em `templates/conciliacao` e `pdf/conciliacao.php` na validação |

## 9. Estratégia de implementação

Etapas pequenas, cada uma terminando com a suíte (ou o recorte pertinente) passando:

- [x] **Etapa 0:** Backup de `storage/database.sqlite` (para a pasta de rascunhos da sessão) antes de qualquer migração.
- [x] **Etapa 1 (dados e domínio):** migração 008; métodos de receita no `ConciliacaoRepository`; `Validator::conciliacaoReceita`; `TIPOS` e `rotuloReceita` no calculador. Testes de repositório, validador e rótulo.
- [x] **Etapa 2 (serviço, controller, rotas):** serviço lê da tabela e invalida; controller e rotas novos; remoção de `salvarReceitas`, `restaurar`, rota `restaurar`, `OverrideRepository`, `receitasCalculadas`, `aplicarOverrides`, `MIN_SLOTS`; ajuste do construtor no `bootstrap`.
- [x] **Etapa 3 (telas e PDF):** seção 1 da tela como lista; `receita_form.php`; mensagem do PDF desatualizado; PDF com vazio e rótulos "Tipo - descrição"; atualizar o hash.
- [x] **Etapa 4 (balancete):** `DEPENDENTES` sem `'15'`; ajustar `BalanceteFluxoTest`.
- [x] **Etapa 5 (testes):** reescrever `ConciliacaoTest` para o novo comportamento; suíte completa.
- [x] **Etapa 6 (validação):** conferir no navegador (lista, adicionar, editar, excluir, mover, KPIs) e o PDF renderizado em imagem (com e sem receitas); revisar CA-01 a CA-08; relatório.
- [x] **Etapa 7 (documentação):** `HISTORICO.md`, `VALIDACAO-FINAL.md`, nota de versão na spec principal e status da spec/plano.

Pontos de pausa: ao fim da Etapa 6, antes de documentar e de qualquer commit (o commit só quando você pedir).

## 10. Testes e validações

- [ ] **Funcional:** criar, editar, excluir e mover receitas; lista começa vazia (CA-01); totais, KPIs e saldo refletem a lista (CA-04).
- [ ] **Regras de negócio:** várias receitas do mesmo tipo; ordem respeitada; rótulo "Tipo - descrição" com e sem descrição (CA-05); sem mínimo de linhas.
- [ ] **Independência do balancete:** lançamentos do balancete não aparecem nem alteram totais da conciliação (CA-02); mudar o balancete não deixa o doc 15 desatualizado (CA-03).
- [ ] **Exceções:** valor vazio, inválido ou zero recusado; tipo inválido recusado; receita de outro projeto ou inexistente → 404 sem alterar nada (CA-07).
- [ ] **PDF:** texto extraído (Ghostscript) com receitas numeradas, e caso vazio com "Nenhuma receita informada."; conferência visual em imagem.
- [ ] **Regressão:** notas, devoluções, extrato, assinaturas e totais de despesas inalterados (CA-08); suíte completa verde (hoje 328); busca por "balancete" na tela e no PDF da conciliação (CA-06).

## 11. Critérios de conclusão

CA-01 a CA-08 da spec atendidos; suíte completa passando; nenhuma referência a `overrides`, `receitasCalculadas`, `aplicarOverrides` ou `OverrideRepository` no código; hash do PDF atualizado; documentação sincronizada; relatório final entregue.

## 12. Decisões técnicas pendentes

- [x] DT-1: Os testes antigos de sobrescrita serão **removidos** (não adaptados), pois o comportamento deixa de existir. Recomendado.
- [x] DT-2: Backup do banco na **pasta de rascunhos** da sessão (fora do projeto). Recomendado.
- [x] DT-3: Mensagem do PDF desatualizado vira "Os dados mudaram desde a última geração." (sem citar o balancete). Recomendado.

## 13. Aprovação

- [x] Especificação aprovada (2026-11-13).
- [x] Planejamento aprovado.
- [x] Autorização para implementação.
