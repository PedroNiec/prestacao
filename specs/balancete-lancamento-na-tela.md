# SPEC — Balancete: lançar na própria tela

**Versão:** 1.3
**Status:** Concluída (2026-11-12) — aprovada pelo usuário
**Data:** 2026-11-12
**Relacionada a:** `specs/prestacao-de-contas.md` (RF-13, RN-04, RN-08, RN-09).
Esta spec **não altera regras de negócio** do balancete; muda **onde** o lançamento é digitado.

---

## 1. Contexto

Hoje, para lançar no balancete, a pessoa clica em "Novo lançamento" e vai para **outra página**
(`/balancete/lancamentos/novo`). Enquanto digita, ela **não vê** o que já foi lançado nem os totais, e precisa voltar
para conferir. O usuário pediu para "no próprio momento do lançamento acompanhar o que já tem lançado".

Escolhas do usuário (2026-11-12):

| Tema | Escolha |
|---|---|
| Formato | **Formulário na própria tela do balancete**, acima da tabela |
| Depois de salvar | **Limpar tudo**: o formulário volta ao padrão (Despesa, sem data, Convênio) |

## 2. Objetivo

Lançar sem sair da tela do balancete, vendo a lista e os totais atualizados a cada lançamento.

## 3. Usuários envolvidos

Um único usuário.

## 4. Requisitos funcionais

- **RF-BL01 Cartão "Novo lançamento"** na tela do balancete, entre o cartão do PDF e os indicadores (Receita, Despesa,
  Saldo), com os **mesmos campos e regras** do formulário atual: tipo, descrição, valor, data, origem, fonte (com o
  preenchimento automático da fonte pela origem, de `lancamento-form.js`), boletim/parcela, CNPJ, nota fiscal, nota de
  empenho e DOC.
- **RF-BL02 Campos de documentos recolhidos:** boletim/parcela, CNPJ, NF, empenho e DOC ficam num bloco "Documentos
  (NF, empenho, CNPJ…)" que abre com um clique (`<details>`, sem JavaScript novo). Ele já vem aberto quando há erro ou
  valor digitado num desses campos.
- **RF-BL03 Salvar:** grava pela mesma rota de hoje (`POST /balancete/lancamentos`) e volta para a tela do balancete
  com:
  - a mensagem "Lançamento adicionado: <descrição> — R$ <valor>";
  - a tabela e os totais já atualizados;
  - o **lançamento novo destacado** na tabela (ver DEC-BL2);
  - o formulário **limpo** (decisão do usuário).
- **RF-BL04 Erro de validação:** a tela volta com a mensagem de erro e o formulário com **o que foi digitado**
  (comportamento atual, agora na própria tela).
- **RF-BL05 Editar** continua numa página própria, como hoje (o formulário da tela é só para lançamentos novos).
- **RF-BL06** O botão "Novo lançamento" do cabeçalho passa a levar ao formulário na própria tela (âncora
  `#novo-lancamento`, com o foco no primeiro campo).
- **RF-BL07** O botão **"Salvar e adicionar outro"** deixa de existir, porque o formulário já fica pronto para o
  próximo (ver DEC-BL1).
- **RF-BL08** Lista vazia: o formulário aparece e o estado vazio diz "Nenhum lançamento ainda. Comece lançando a
  transferência recebida (receita)." (texto atual).

### Esboço

```
Projetos › Aquisição de um veículo… › 01 Balancete
01 — Balancete
┌ PDF do documento ───────────────────────── [Gerar PDF] Ver Baixar ┐
┌ Novo lançamento ──────────────────────────────────────────────────┐
│ Tipo: ( ) Receita (entrou na conta)  (•) Despesa (saiu da conta)   │
│ Descrição [______________________________]  Valor (R$) [________]  │
│ Data [__/__/____]   Origem [Convênio ▾]   Fonte [1 - Concedente ▾] │
│ ▸ Documentos (NF, empenho, CNPJ…)                                  │
│                                              [Salvar lançamento]   │
└────────────────────────────────────────────────────────────────────┘
[Receita total 497.152,49] [Despesa total 491.186,66] [Saldo 5.965,83]
Lançamentos (6)
 Parc. | Descrição               | … | Receita     | Despesa
 1     | Transferência recebida  | … | 497.152,49  |
 1     | Aquisição ônibus  ◀ novo| … |             | 491.186,66
```

## 5. Requisitos não funcionais

- Sem dependências novas e sem JavaScript novo (o `lancamento-form.js` existente continua cuidando da fonte).
- CSS: o arquivo está em 39,2 KB, com limite de 40 KB; reaproveitar classes existentes. Previsto: só o destaque da
  linha nova.
- Acessibilidade: rótulos dos campos preservados; o destaque da linha nova tem texto ("novo"), não só cor.

## 6. Regras de negócio

Nenhuma alterada (RN-04, RN-08, RN-09 e as validações do `Validator::lancamento()` continuam iguais). Mesma rota,
mesmos campos, mesmo CSRF.

## 7. Fluxos de utilização

**Principal:** Balancete → digitar no cartão "Novo lançamento" → Salvar → ver o lançamento destacado na tabela e os
totais atualizados → digitar o próximo.

**Alternativos:** editar (página própria, como hoje); excluir e reordenar (como hoje).

## 8. Exceções e erros

- EX-BL01 Validação falha → mensagem e formulário com o que foi digitado, na própria tela.
- EX-BL02 Acesso direto ao endereço antigo `/balancete/lancamentos/novo` (favorito, histórico do navegador) → leva à
  tela do balancete, no formulário (ver DEC-BL3).

## 9. Critérios de aceitação

- [x] CA-BL01 A tela do balancete tem o formulário de novo lançamento com todos os campos atuais, e a fonte continua
      automática pela origem.
- [x] CA-BL02 Salvar volta à tela com a mensagem, a tabela e os totais atualizados, a linha nova destacada e o
      formulário limpo.
- [x] CA-BL03 Erro de validação mantém o que foi digitado, na própria tela.
- [x] CA-BL04 Editar continua funcionando na página própria.
- [x] CA-BL05 Nenhuma falha nova nos testes; os testes que mudam estão registrados (§10).
- [x] CA-BL06 Conferência do usuário no navegador.

## 10. Restrições técnicas

- `BalanceteFluxoTest::testSaveAndAddAnotherKeepsContext` testa o "Salvar e adicionar outro" (que manda de volta ao
  formulário com origem e data). Com a DEC-BL1, esse teste **muda** para o comportamento novo (registrado).
- `BalanceteFluxoTest::testInvalidLancamentoIsNotSavedAndKeepsInput` espera o retorno ao formulário antigo; passa a
  esperar a tela do balancete (registrado).
- `TelasPadraoTest` confere os `id`s usados pelo `lancamento-form.js` (`form-lancamento`, `origem`, `fonte`) e o botão
  "Cancelar" no formulário de lançamento. Os `id`s ficam no formulário da tela; o "Cancelar" continua no formulário
  de **edição**.

## 11. Fora do escopo

- Editar na própria tela ("edição na linha").
- Lançar várias linhas de uma vez, importar de planilha, atalhos de teclado.
- Mudanças em outras telas (físico-financeiro, conciliação, relação de bens).

## 12. Decisões pendentes

- [x] DEC-BL1 **Aprovada.** **Remover o "Salvar e adicionar outro"** (RF-BL07). *Recomendo aprovar:* com o formulário fixo na tela e
      limpo após salvar, ele faria o mesmo que "Salvar". *Padrão: aprovar.*
- [x] DEC-BL2 **Aprovada.** **Destacar o lançamento recém-salvo** na tabela (fundo azul-claro e a etiqueta "novo"). *Recomendo
      aprovar:* confirma visualmente onde ele entrou. *Padrão: aprovar.*
- [x] DEC-BL3 **Aprovada.** **Endereço antigo** `/balancete/lancamentos/novo` passa a redirecionar para a tela do balancete
      (`#novo-lancamento`). *Recomendo aprovar:* não quebra favoritos. *Padrão: aprovar.*

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação, a partir do pedido do usuário e das escolhas (formulário na tela, limpar tudo após salvar) | 2026-11-12 |
| 1.1 | Aprovada pelo usuário com as recomendações (DEC-BL1, BL2 e BL3) | 2026-11-12 |
| 1.2 | **Implementada.** 328/328 testes (6 novos). Testes ajustados e registrados: `BalanceteFluxoTest` (o de "Salvar e adicionar outro" virou `testFormIsCleanAfterSaving`; o de erro passou a esperar a tela do balancete) e `TelasPadraoTest` (referências à página "novo" trocadas pela edição/tela). Conferido no servidor de demonstração | 2026-11-12 |
| 1.3 | Conferida e aprovada pelo usuário ("ficou bom") | 2026-11-12 |
