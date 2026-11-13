# SPEC — Redesenho visual: lista de Projetos (Fase 3.2)

**Versão:** 1.3
**Status:** Concluída (2026-11-12) — aprovada pelo usuário
**Data:** 2026-11-12
**Relacionada a:** `specs/redesenho-layout-municipios.md` (fase 3.1, **padrão visual aprovado**).
Esta spec **não altera funcionalidades nem regras de negócio**.

---

## 1. Contexto

O usuário aprovou o estilo da fase 3.1 (menu lateral claro e cartões de Municípios) e pediu para seguir tela por tela,
agora pela **lista de Projetos** (`/projetos`, também a tela inicial: `/` redireciona para ela).

Hoje (fase 2.2) a lista já está em cartões empilhados, mas no estilo antigo: linhas dentro de uma única caixa, objeto
como link pequeno e botão "Abrir" à direita.

Escolhas do usuário (2026-11-12):

| Tema | Escolha |
|---|---|
| Formato | **Lista em linhas largas**: um cartão por linha, ocupando a largura toda (objetos longos cabem em 1 ou 2 linhas) |
| Andamento dos documentos ("N de 25") | **Não mostrar** (coerente com a DEC-P1 da tela do Projeto) |

## 2. Objetivo

Deixar a lista de Projetos no mesmo estilo de Municípios, com cada projeto fácil de reconhecer e de abrir.

## 3. Usuários envolvidos

Um único usuário.

## 4. Requisitos funcionais

- **RF-LP01 Cabeçalho:** título "Projetos", subtítulo com a contagem ("1 projeto" / "N projetos") e botão
  **"+ Novo projeto"** à direita.
- **RF-LP02 Um cartão por projeto**, empilhados e com espaço entre si (mesmo arredondamento, borda e sombra dos
  cartões de Municípios). Cada cartão tem:
  - um **ícone de pasta** num quadrado azul-claro (o mesmo ícone do menu);
  - o **objeto** em destaque (texto inteiro, quebrando em mais linhas se preciso);
  - a linha secundária "Município - UF · Processo · #N" (sem o processo quando estiver vazio);
  - à direita, a indicação **"Abrir ›"**.
- **RF-LP03 O cartão inteiro é clicável** e leva ao projeto (ver DEC-LP1). Ao passar o mouse, o cartão ganha
  destaque (borda e sombra, como em Municípios). Pelo teclado, o foco fica visível no cartão.
- **RF-LP04 Lista vazia:** estado vazio com ícone, a frase atual ("Nenhum projeto cadastrado ainda.") e o botão
  certo: "Cadastrar município" se não houver município, ou "Novo projeto".
- **RF-LP05 Celular (≤ 640 px):** o cartão empilha (ícone e objeto em cima, linha secundária e "Abrir ›" abaixo),
  sem rolagem horizontal.

### Esboço — desktop

```
Projetos                                                  [+ Novo projeto]
2 projetos

╭───────────────────────────────────────────────────────────────────────╮
│ ▢  AQUISIÇÃO DE UM VEÍCULO ÔNIBUS PARA O TRANSPORTE ESCOLAR            │
│    Caxambu do Sul - SC · PROCESSO SCC 7909/2025 · #1          Abrir ›  │
╰───────────────────────────────────────────────────────────────────────╯
╭───────────────────────────────────────────────────────────────────────╮
│ ▢  PAVIMENTAÇÃO ASFÁLTICA DA RUA DAS FLORES, TRECHO ENTRE A AVENIDA    │
│    CENTRAL E A RUA DO COMÉRCIO                                         │
│    Caxambu do Sul - SC · PROCESSO SCC 1234/2026 · #2          Abrir ›  │
╰───────────────────────────────────────────────────────────────────────╯
```

## 5. Requisitos não funcionais

- Só HTML e CSS; sem JavaScript novo e sem dependências.
- **Acessibilidade:** um único link por projeto (o objeto); o clique no cartão inteiro é obtido "esticando" esse link
  pelo CSS, sem duplicar links para o leitor de tela. "Abrir ›" é só visual; o ícone é decorativo.
- Tudo escapado com `e()`.

## 6. Regras de negócio

Nenhuma alterada. Mesma ordem de projetos de hoje.

## 7. Fluxos de utilização

Os mesmos: ver a lista, abrir um projeto, criar um projeto.

## 8. Exceções e erros

- EX-LP01 Projeto sem objeto (dado antigo): o título mostra "Projeto #N".
- EX-LP02 Objeto muito longo: quebra em várias linhas, sem estourar o cartão.

## 9. Critérios de aceitação

- [x] CA-LP01 Cabeçalho com contagem no singular e no plural e o botão "+ Novo projeto".
- [x] CA-LP02 Cada projeto é um cartão com ícone, objeto, município, processo (quando houver), número e "Abrir ›".
- [x] CA-LP03 Clicar em qualquer parte do cartão abre o projeto; há exatamente um link por projeto.
- [x] CA-LP04 Lista vazia com o estado vazio e o botão certo (com e sem município cadastrado).
- [x] CA-LP05 Sem rolagem horizontal a 360 px.
- [x] CA-LP06 Nenhuma falha nova nos testes.
- [x] CA-LP07 Conferência visual do usuário.

## 10. Restrições técnicas

- `TelasPadraoTest` (fase 2.2) verifica `<a class="item-titulo" href="/projetos/N">objeto</a>` e o estado vazio em
  `/projetos`. O link do objeto ganha uma classe nova para "esticar" o clique, então **essa asserção (escrita por mim
  na fase 2.2) precisa de ajuste**, que fica registrado.
- O teste antigo `HttpTest` procura o texto "Projetos" na página; ele continua lá.
- Arquivos previstos: `templates/projetos/index.php` e `public/assets/app.css` (nova seção, reaproveitando o
  ícone de pasta do menu).

## 11. Fora do escopo

- Barra de andamento ("N de 25"), busca, filtro, ordenação e exclusão pela lista.
- Formulário de projeto e tela do Projeto (próximas fases).

## 12. Decisões pendentes

- [x] DEC-LP1 **Aprovada.** **Cartão inteiro clicável** (RF-LP03). *Recomendo aprovar:* área de clique grande e comportamento
      esperado em listas modernas. *Alternativa:* só o objeto e o "Abrir ›" são clicáveis. *Padrão: aprovar.*

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação, a partir das escolhas do usuário (linhas largas, sem andamento) e do padrão visual da fase 3.1 | 2026-11-12 |
| 1.1 | Aprovada pelo usuário com as recomendações (DEC-LP1 aprovada) | 2026-11-12 |
| 1.2 | **Implementada.** 295 testes, nenhuma falha nova. Ajustes registrados em 2 asserções da `TelasPadraoTest` (fase 2.2): classe do link do objeto (`projeto-link`) e estado vazio com variante de ícone. O ícone de pasta virou o token `--icone-pasta` (menu e lista). CSS em 39,2 KB, perto do limite de 40 KB | 2026-11-12 |
| 1.3 | Conferida e aprovada pelo usuário ("ficou boa") | 2026-11-12 |
