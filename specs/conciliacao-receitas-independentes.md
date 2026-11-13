# SPEC — Conciliação bancária: receitas independentes do balancete

**Versão:** 1.0
**Status:** Implementada (2026-11-13)
**Data:** 2026-11-13

---

## 1. Contexto

A conciliação bancária (documento 15) tem hoje três partes: **receitas**, **despesas (notas fiscais)** e **extrato/devoluções**.
Só as despesas e o extrato são digitados na própria conciliação. As **receitas são calculadas a partir do balancete**
(1º a 3º repasse, 1ª a 3ª contrapartida e rendimentos), podendo ser sobrescritas uma a uma, com a marca "alterado" e o botão
"Restaurar" (decisão da Etapa 8). Além disso, qualquer mudança no balancete marca a conciliação como "desatualizada".

## 2. Objetivo

Tornar as receitas da conciliação **totalmente independentes do balancete**: o usuário lança as receitas direto na conciliação,
numa lista livre, e o balancete deixa de influenciar a conciliação em qualquer ponto.

## 3. Usuários envolvidos

Uma pessoa, sem login e sem perfis (como no restante do sistema).

## 4. Requisitos funcionais

- RF-01: A seção "1. Receitas" da conciliação passa a ser uma **lista livre**: o usuário **adiciona, edita, exclui e reordena** receitas.
- RF-02: Cada receita tem **tipo** (Repasse, Contrapartida ou Rendimentos), **descrição** (texto livre, opcional) e **valor**.
- RF-03: A lista **começa vazia** em todo projeto; nada é preenchido a partir do balancete.
- RF-04: O **total de receitas** é a soma dos valores da lista e alimenta os KPIs, o saldo e a conciliação como hoje.
- RF-05: No PDF, cada receita aparece numerada (1.1, 1.2, …) com o texto **"Tipo - descrição"** (ex.: "Repasse - Parcela 1 - OB 123"); se a descrição estiver vazia, só o tipo.
- RF-06: Sem receitas, o PDF mostra a linha "Nenhuma receita informada." e total zero (como já faz para notas fiscais).
- RF-07: O balancete **deixa de ler e de invalidar** a conciliação: lançar, editar ou excluir lançamentos no balancete não marca o documento 15 como desatualizado.
- RF-08: Adicionar, editar, excluir ou reordenar uma receita marca o documento 15 como **desatualizado**.
- RF-09: Somem a marca "alterado", o botão "Restaurar" e o texto "calculadas a partir do balancete".

## 5. Requisitos não funcionais

- Segurança: texto dinâmico com `e()`, POST com CSRF, valores em centavos (inteiros), como no restante do sistema.
- Usabilidade: a lista segue o padrão visual e de interação já usado nas **notas fiscais da conciliação** e nos lançamentos do balancete; o CSS tem **folga de apenas 45 bytes** (limite de 40 KB), então a implementação deve reaproveitar classes existentes ou limpar CSS antigo.
- Compatibilidade: Linux e Windows, sem dependências novas.

## 6. Regras de negócio

- RN-01: Tipos permitidos: Repasse, Contrapartida, Rendimentos. Pode haver **várias** receitas do mesmo tipo (inclusive vários rendimentos).
- RN-02: Valor obrigatório e **maior que zero** (mesmo formato de dinheiro do sistema, ex.: 1.234,56).
- RN-03: Descrição opcional, com tamanho máximo a definir no plano (mesmo critério dos demais campos de texto).
- RN-04: Não há quantidade mínima de linhas (a regra antiga de "pelo menos 3 repasses e 3 contrapartidas" deixa de existir).
- RN-05: A ordem da lista é a ordem de exibição na tela e no PDF.
- RN-06: Despesas (notas fiscais), devoluções, extrato e a regra "Total de despesas = soma do bruto das notas" **não mudam**.

## 7. Fluxos de utilização

### Fluxo principal

1. Na conciliação, seção "1. Receitas", o usuário clica em adicionar.
2. Escolhe o tipo, digita a descrição (opcional) e o valor, e salva.
3. A receita aparece na lista; o total, os KPIs e o saldo se atualizam; o documento 15 fica "desatualizado".
4. O usuário gera o PDF e vê as receitas numeradas com "Tipo - descrição".

### Fluxos alternativos

1. Editar uma receita (valor, tipo ou descrição).
2. Excluir uma receita (com confirmação).
3. Mover uma receita para cima ou para baixo.

## 8. Exceções e erros

- EX-01: Valor vazio, inválido ou zero → mensagem de erro e nada é salvo.
- EX-02: Tipo fora dos três permitidos → rejeitado.
- EX-03: Receita de outro projeto, ou inexistente (id manipulado) → não encontrada, sem alterar nada.

## 9. Critérios de aceitação

- [x] CA-01: Em um projeto novo, a seção Receitas aparece vazia e o total é 0,00.
- [x] CA-02: Lançamentos do balancete (receita ou despesa) **não** aparecem na conciliação, nem mudam seus totais.
- [x] CA-03: Mudar o balancete **não** marca o documento 15 como desatualizado.
- [x] CA-04: Adicionar, editar, excluir e reordenar receitas funcionam e marcam o documento 15 como desatualizado.
- [x] CA-05: O PDF mostra as receitas na ordem, numeradas, com "Tipo - descrição" e o total correto; vazio mostra "Nenhuma receita informada.".
- [x] CA-06: Não existem mais "alterado", "Restaurar" nem referências ao balancete na tela da conciliação.
- [x] CA-07: Valor zero, vazio ou inválido é recusado com mensagem; id de outro projeto é recusado.
- [x] CA-08: Os demais comportamentos (notas, devoluções, extrato, totais, assinaturas) permanecem iguais; a suíte de testes passa.

## 10. Restrições técnicas

- Nova tabela para as receitas da conciliação (migração 008). A tabela `overrides` só é usada pela conciliação e fica sem uso; a remoção dela e do código associado (cálculo a partir do balancete, sobrescritas, rota "restaurar") será detalhada no plano.
- Os dados atuais de receitas dos projetos existentes são **de teste** e podem ser descartados (decisão do usuário em 2026-11-13); **não há conversão** de dados.
- Os testes atuais que cobrem o cálculo a partir do balancete e as sobrescritas serão substituídos por testes do novo comportamento.
- O hash de `templates/pdf/conciliacao.php` em `tests/DesignSystemTest.php` precisará ser atualizado de propósito.

## 11. Fora do escopo

- Qualquer vínculo entre balancete e conciliação (importar, sugerir, comparar).
- Mudanças nas despesas, nas devoluções, no extrato bancário ou no cabeçalho/assinaturas da conciliação.
- Mudanças em outros documentos (físico-financeiro, relação de bens).
- A premissa das devoluções fora do total de despesas (HISTORICO §6), que segue em aberto.

## 12. Decisões pendentes

Aprovadas em 2026-11-13 (com as recomendações):

- [x] DEC-01: **Remover** a tabela `overrides` e o código de sobrescritas (recomendado: sim, ficam sem uso e confundem). Alternativa: manter a tabela sem uso.
- [x] DEC-02: Valor da receita **maior que zero** (recomendado). Alternativa: aceitar zero.
- [x] DEC-03: Descrição **opcional** (recomendado), pois o tipo já identifica a linha no PDF.

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação inicial (descoberta: lista livre, tipo + descrição, "Tipo - descrição" no PDF, dados atuais descartáveis) | 2026-11-13 |
| 1.1 | Aprovada com as recomendações; implementada e validada | 2026-11-13 |
