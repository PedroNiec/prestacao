# SPEC — Redesenho visual: novo layout (menu lateral) + tela de Municípios (Fase 3.1)

**Versão:** 1.3
**Status:** Concluída (2026-11-12) — aprovada pelo usuário no navegador
**Data:** 2026-11-12
**Relacionada a:** `specs/melhoria-visual-fundacao.md`, `specs/melhoria-visual-tela-projeto.md`,
`specs/melhoria-visual-demais-telas.md`. Esta spec **não altera funcionalidades nem regras de negócio**.

---

## 1. Contexto

As fases 2.1 e 2.2 reorganizaram as telas, mas mantiveram a base visual da fundação: barra azul sólida no topo,
fundo cinza, cantos pouco arredondados, conteúdo apertado. Ao conferir, o usuário avaliou que o sistema **"ainda tem
cara antiga"** e pediu para ir **tela por tela, começando por `/municipios`**.

Escolhas do usuário (2026-11-12):

| Tema | Escolha |
|---|---|
| Direção visual | **Menu lateral moderno**: cartões bem arredondados, mais espaço em branco |
| Cor do menu lateral | **Claro (branco)**, com o item ativo em azul-claro |
| "Excluir" no cartão do município | **Escondido no menu ⋮** |
| Ritmo | Tela por tela. Municípios é o **piloto** e define o estilo que as próximas herdam |

## 2. Objetivo

1. Trocar o **layout geral** (topo azul → menu lateral claro) para o sistema inteiro ganhar cara atual.
2. Redesenhar a **tela de Municípios** (lista) como piloto do novo estilo.

As outras telas recebem o novo layout (menu lateral) imediatamente; o miolo delas será redesenhado nas próximas fases,
uma por vez.

## 3. Usuários envolvidos

Um único usuário (quem prepara a prestação de contas).

## 4. Requisitos funcionais

### 4.1 Layout geral (`templates/layout.php`), vale para todas as telas

- **RF-L01 Menu lateral fixo à esquerda** (≈ 240 px), fundo branco com borda fina à direita:
  - marca "Prestação de Contas" no topo do menu, com um ícone;
  - itens **Projetos** e **Municípios**, cada um com ícone; o item ativo com fundo azul-claro e texto azul;
  - rodapé do menu com "Guia visual".
- **RF-L02 Área de conteúdo** à direita, com fundo cinza bem claro e mais respiro nas margens. O rodapé atual (com o
  link do Guia visual) continua no fim do conteúdo.
- **RF-L03 Tela estreita (≤ 900 px):** o menu vira uma **barra no topo** (marca + Projetos + Municípios, lado a lado),
  sem botão de menu; o conteúdo ocupa a largura toda.
- **RF-L04 Ajustes de base (tokens):** cantos mais arredondados nos cartões (≈ 12 px), sombras mais suaves, fundo
  mais claro e títulos de página maiores. Como são tokens, **todas as telas** mudam um pouco (mais arredondadas e
  leves), sem mudar a estrutura delas.
- **RF-L05** Mensagens fixas, "pular para o conteúdo", foco visível e marcação do item ativo **continuam** como hoje.

### 4.2 Tela de Municípios (`/municipios`)

- **RF-M01 Cabeçalho:** título "Municípios", subtítulo com a contagem ("2 municípios cadastrados", ou "1 município
  cadastrado") e o botão **"+ Novo município"** à direita.
- **RF-M02 Grade de cartões** (2 ou 3 por linha, conforme a largura; 1 no celular). Cada cartão tem:
  - **iniciais** do município num círculo azul-claro (por exemplo, "CS" para Caxambu do Sul; ignora "do", "da",
    "de", "dos" e "das");
  - nome do município em destaque e, abaixo, "SC · 3 projeto(s)";
  - **Prefeito** e **Contador** (com o CRC), com rótulo pequeno; "—" quando vazio;
  - rodapé do cartão: **Editar** e o menu **⋮**.
- **RF-M03 Menu ⋮:** abre no lugar (componente nativo `<details>`, sem JavaScript) e contém **Excluir**, com a
  mesma confirmação e a mesma rota de hoje.
- **RF-M04 Município com projetos:** dentro do menu ⋮, o "Excluir" aparece **desabilitado**, com a explicação
  "Possui N projeto(s)". Hoje o sistema já recusa essa exclusão com uma mensagem de erro; a tela só passa a avisar
  antes (ver DEC-L2).
- **RF-M05 Lista vazia:** estado vazio com ícone, a frase atual e o botão "Novo município".

### Esboço — desktop

```
┌────────────────┬──────────────────────────────────────────────────────────────┐
│ ▣ Prestação    │                                                              │
│   de Contas    │  Municípios                              [+ Novo município]  │
│                │  2 municípios cadastrados                                    │
│ ◫ Projetos     │                                                              │
│▐◉ Municípios ▌ │  ╭──────────────────────────╮  ╭──────────────────────────╮  │
│                │  │ (CS)  Caxambu do Sul     │  │ (CH)  Chapecó            │  │
│                │  │       SC · 3 projeto(s)  │  │       SC · 0 projeto(s)  │  │
│                │  │                          │  │                          │  │
│                │  │ PREFEITO  Edi Marcos     │  │ PREFEITO  —              │  │
│                │  │ CONTADOR  Maicon Luft    │  │ CONTADOR  —              │  │
│                │  │           CRC/SC 102.316 │  │                          │  │
│                │  │ ──────────────────────── │  │ ──────────────────────── │  │
│ Guia visual    │  │ Editar                ⋮  │  │ Editar                ⋮  │  │
└────────────────┴──╰──────────────────────────╯──╰──────────────────────────╯──┘
```

### Esboço — celular (≈ 360 px)

```
▣ Prestação de Contas   Projetos  [Municípios]
──────────────────────────────────────────────
Municípios
2 municípios cadastrados
[+ Novo município]
╭────────────────────────────────────────────╮
│ (CS) Caxambu do Sul · SC · 3 projeto(s)    │
│ PREFEITO  Edi Marcos                       │
│ CONTADOR  Maicon Luft (CRC/SC 102.316)     │
│ Editar                                  ⋮  │
╰────────────────────────────────────────────╯
```

## 5. Requisitos não funcionais

- **Sem dependências e sem recursos externos:** ícones desenhados em SVG embutido no CSS, sem fontes baixadas.
- **Sem JavaScript novo:** o menu ⋮ usa `<details>`. Limitação conhecida: ele fecha clicando de novo no ⋮, e não ao
  clicar fora.
- **Acessibilidade:** menu lateral em `<nav aria-label="Principal">` com `aria-current="page"`; ícones decorativos
  ocultos para leitores de tela; o "⋮" tem nome acessível ("Mais ações para Caxambu do Sul"); contraste mínimo de
  4,5:1 no texto.
- **Tamanho do CSS:** o novo layout e o piloto passam do limite atual de 30 KB. **Subir o limite para 40 KB**,
  conforme o critério já aprovado na DEC-T5 (sistema local, sem custo de rede).

## 6. Regras de negócio

Nenhuma alterada.

- RN-L01 A exclusão de município com projetos **continua bloqueada no servidor** (mensagem atual). O aviso na tela é
  só uma antecipação.
- RN-L02 Rotas, campos, CSRF e confirmação (`confirm('Excluir este município?')`) inalterados.

## 7. Fluxos de utilização

Os mesmos de hoje: listar, criar, editar e excluir município. Muda só a navegação, agora pelo menu lateral.

## 8. Exceções e erros

- EX-L01 Nome de município com uma palavra só ("Chapecó"): as iniciais são as duas primeiras letras ("CH").
- EX-L02 Nome muito longo: quebra em mais linhas dentro do cartão, sem estourar.
- EX-L03 Prefeito ou contador vazios: "—".

## 9. Critérios de aceitação

- [x] CA-L01 Todas as telas mostram o menu lateral (≥ 901 px) ou a barra no topo (≤ 900 px), com o item ativo marcado.
- [x] CA-L02 `/municipios` mostra a grade de cartões com iniciais, nome, UF, nº de projetos, prefeito e contador.
- [x] CA-L03 "Excluir" fica só dentro do menu ⋮, com a confirmação; desabilitado (com o motivo) quando há projetos.
- [x] CA-L04 Com 360 px de largura não há rolagem horizontal.
- [x] CA-L05 Os testes existentes passam sem nenhuma falha nova; o único teste alterado é o limite do CSS (30 → 40 KB).
- [x] CA-L06 Conferência visual do usuário: a tela "não tem mais cara antiga".

## 10. Restrições técnicas

- **Testes do layout que devem continuar válidos:** `aria-label="Principal"`; os links do menu no formato
  `<a href="…" aria-current="page">Projetos</a>`, **só com texto dentro** (o teste lê o texto do link; por isso os
  ícones ficam no CSS e não dentro do link); `class="pular-link"`; `id="conteudo"`; o link `/guia-visual`; a
  região de mensagens.
- **Testes de Municípios:** `onsubmit="return confirm('Excluir este município?')"`; `class="tag info"` com
  "N projeto(s)" (`TelasPadraoTest`); estado vazio.
- Arquivos previstos (detalhe no plano): `templates/layout.php`, `templates/municipios/index.php`,
  `public/assets/app.css` (tokens, layout, seção da tela) e um pequeno apresentador das iniciais (testável).

## 11. Fora do escopo

- O miolo das outras telas (próximas fases, uma por vez).
- Formulário de município (próxima tela, se você quiser).
- Busca e filtro de municípios, modo escuro, menu recolhível.
- PDFs, regras, banco e rotas.

## 12. Decisões pendentes

- [x] DEC-L1 **Aprovada.** **Ajustar os tokens globais** (cantos, sombras, fundo, títulos) já nesta fase (RF-L04), para todas as
      telas. *Recomendo aprovar:* sem isso, o menu novo convive com cartões "antigos" nas outras telas.
      *Padrão: aprovar.*
- [x] DEC-L2 **Aprovada.** **"Excluir" desabilitado quando o município tem projetos** (RF-M04). *Recomendo aprovar:* evita clicar,
      confirmar e só então receber o erro. *Padrão: aprovar.*
- [x] DEC-L3 **Aprovada.** **Limite do CSS 30 → 40 KB** (§5). *Recomendo aprovar.* *Padrão: aprovar.*

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação: novo layout com menu lateral claro e redesenho da tela de Municípios (piloto), a partir das escolhas do usuário | 2026-11-12 |
| 1.1 | Aprovada pelo usuário com as recomendações (DEC-L1, L2 e L3 aprovadas) | 2026-11-12 |
| 1.2 | **Implementada.** CA-L01/02/03/05 verificados (290 testes, nenhuma falha nova). Testes alterados, todos registrados: limite do CSS 30 → 40 KB (DEC-L3) e 2 asserções da `TelasPadraoTest` (fase 2.2) sobre a lista de municípios (contagem agora no subtítulo do cartão; com projetos o Excluir fica desabilitado). Correção incidental: aspas no indicador da conciliação impediam o teste de classes de ler o atributo. CA-L04 e CA-L06 aguardam o usuário | 2026-11-12 |
| 1.3 | Conferida e aprovada pelo usuário ("ficou perfeito, dessa maneira mesmo que quero seguir"). Este estilo é o **padrão** para as próximas telas | 2026-11-12 |
