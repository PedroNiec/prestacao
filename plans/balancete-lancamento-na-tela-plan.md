# PLAN — Balancete: lançar na própria tela

**Versão:** 1.0
**Status:** Concluído (2026-11-12) — aprovado pelo usuário
**SPEC relacionada:** `specs/balancete-lancamento-na-tela.md` (v1.1, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Os campos do formulário de lançamento passam para um pedaço de template compartilhado (`balancete/_campos.php`),
usado **na tela do balancete** (novo lançamento) e na **página de edição**. O controlador muda para onde redireciona:
depois de criar, ou de um erro ao criar, volta à tela do balancete. O endereço antigo "novo" redireciona. Banco,
regras, rota de gravação e `lancamento-form.js` ficam iguais.

## 2. Análise da arquitetura atual

- `BalanceteController::novo()` renderiza `balancete/form.php`; `criar()` valida com `Validator::lancamento()` e, em
  caso de erro, guarda os dados em `Session::old()` e redireciona para `/lancamentos/novo`; com `outro`, volta ao
  formulário com tipo, origem, fonte e data na URL.
- `BalanceteService::criar()` **já devolve o id** do lançamento criado (útil para o destaque).
- `Session::old()` é lido uma vez e apagado (serve para devolver o que foi digitado).
- `lancamento-form.js` procura `#origem` e `#fonte` na página: funciona igual se o formulário estiver na tela do
  balancete.
- `balancete/index.php` já tem trilha, cartão do PDF, indicadores, tabela (`<tr id="l<id>">`) e estado vazio.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `templates/balancete/_campos.php` | **Criar** | Campos do lançamento (tipo, descrição, valor, data, origem, fonte e o bloco "Documentos" em `<details>`), usados nos dois formulários |
| `templates/balancete/index.php` | Alterar | Cartão "Novo lançamento" (RF-BL01, BL02), destaque da linha nova (DEC-BL2), botão do cabeçalho para `#novo-lancamento` (RF-BL06), script `lancamento-form.js` |
| `templates/balancete/form.php` | Alterar | Passa a ser **só de edição**; usa `_campos.php`; sem "Salvar e adicionar outro" |
| `src/Controllers/BalanceteController.php` | Alterar | `index()` recebe `Session::old()` e `?novo=<id>`; `criar()` volta à tela (com a mensagem detalhada) e, se houver erro, à tela com o que foi digitado; `novo()` redireciona (DEC-BL3); `outro` deixa de existir |
| `public/assets/app.css` | Alterar | Só a linha destacada (`.linha-nova`), ~0,2 KB |
| `tests/BalanceteFluxoTest.php` | Alterar 2 testes | `testSaveAndAddAnotherKeepsContext` → testa o formulário limpo após salvar; `testInvalidLancamentoIsNotSavedAndKeepsInput` → espera a tela do balancete (registrado na spec §10) |
| `tests/TelasPadraoTest.php` | Alterar | As 4 referências à página "novo lançamento" passam para a tela do balancete ou para a edição (os `id`s do script e o "Cancelar" continuam verificados) |
| `tests/BalanceteNaTelaTest.php` | **Criar** | CA-BL01 a CA-BL04 |
| spec, plano, `docs/HISTORICO.md` | Alterar (no fim) | Registro |

## 4. Banco de dados

**Nenhuma alteração.**

## 5. Serviços e regras de negócio

Nenhuma alteração: `Validator::lancamento()` e `BalanceteService` iguais. Só o controlador e as telas mudam.

## 6. Fluxo técnico

```
GET  /balancete                      → index(): form "novo" com Session::old() (se houver erro) ou padrão
                                        (tipo=despesa, origem=convenio, fonte=1, resto vazio); ?novo=<id> destaca a linha
POST /balancete/lancamentos          → criar():
       erro   → flash(erros) + Session::old(post) + redirect /balancete#novo-lancamento   (EX-BL01)
       ok     → id = service->criar(); flash "Lançamento adicionado: <descrição> — R$ <valor>"
                 + redirect /balancete?novo=<id>#novo-lancamento                          (formulário limpo)
GET  /balancete/lancamentos/novo     → redirect /balancete#novo-lancamento                (DEC-BL3)
GET  /balancete/lancamentos/{id}/editar e POST /balancete/lancamentos/{id} → como hoje (form.php, só edição)
```

- O formulário da tela tem `id="form-lancamento"` e o mesmo `action`. Os campos de documentos ficam em
  `<details class="recolhe">` (componente da tela do Projeto), aberto quando algum deles tem valor (retorno de erro).
- Linha destacada: `<tr id="l<id>" class="linha-nova">` com a etiqueta `<span class="tag info">novo</span>` ao lado
  da descrição (informação também em texto).
- O botão "Novo lançamento" do cabeçalho vira `href="#novo-lancamento"`; o campo Descrição tem `autofocus` só na
  edição (na tela do balancete não rouba a rolagem ao abrir).

## 7. Segurança e permissões

Mesma rota de gravação, CSRF e validação; `?novo=` é convertido para inteiro e só destaca uma linha que já está na
lista do projeto (um id de outro projeto simplesmente não aparece).

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| `Session::old()` lido também por outra tela | Os dados digitados aparecerem no lugar errado | O `old` é gravado só pelo `criar()` com erro e lido logo em seguida pelo `index()`; a edição continua gravando/lendo no seu próprio fluxo. |
| Tela do balancete fica mais comprida | Mais rolagem até a tabela | Documentos recolhidos; o retorno usa `#novo-lancamento`, deixando o formulário, os totais e o início da tabela à vista. |
| Testes antigos que usam a página "novo" | Falhas | Ajustados e registrados (§3). |
| CSS no limite (39,2/40 KB) | Teste de tamanho | Só ~0,2 KB novos; medir. |

## 9. Estratégia de implementação

- [x] **Etapa 1:** `_campos.php`, `form.php` (edição) e o cartão na `index.php`.
- [x] **Etapa 2:** controlador (index com `old` e `novo`, criar, redirect do "novo") e CSS do destaque.
- [x] **Etapa 3:** testes (novos e ajustados), suíte completa, conferência no servidor de demonstração.
- [x] **Etapa 4:** documentos atualizados; conferência sua no navegador (CA-BL06).

## 10. Testes e validações

`BalanceteNaTelaTest`:
- [ ] CA-BL01: a tela do balancete tem `id="form-lancamento"`, `#origem`, `#fonte`, todos os campos, o `<details>` dos
      documentos e o script.
- [ ] CA-BL02: salvar → redirect para `/balancete?novo=<id>#novo-lancamento`; a mensagem com descrição e valor; a linha
      destacada com "novo"; o formulário seguinte vem com os padrões (Despesa, Convênio, fonte 1, campos vazios).
- [ ] CA-BL03: erro → redirect para `/balancete#novo-lancamento`; o formulário da tela traz o que foi digitado; os
      documentos vêm abertos se algum foi preenchido; nada é gravado.
- [ ] CA-BL04: edição continua na página própria, com "Cancelar" e sem "Salvar e adicionar outro".
- [ ] DEC-BL3: `GET /lancamentos/novo` → 303 para `/balancete#novo-lancamento`.
- [ ] `?novo=` com id de outro projeto não destaca nada.

Regressão: suíte completa (meta: 100% passando, como agora).

## 11. Critérios de conclusão

CA-BL01 a CA-BL05 verificados; CA-BL06 com você; documentos atualizados.

## 12. Decisões técnicas pendentes

Nenhuma.

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12).
