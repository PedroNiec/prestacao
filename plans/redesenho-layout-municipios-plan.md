# PLAN — Redesenho visual: novo layout (menu lateral) + tela de Municípios (Fase 3.1)

**Versão:** 1.0
**Status:** Concluído (2026-11-12) — aprovado pelo usuário
**SPEC relacionada:** `specs/redesenho-layout-municipios.md` (v1.1, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Três partes, só apresentação:

1. **Tokens:** cantos, sombras e fundo, valendo para todas as telas.
2. **Layout:** `layout.php` com o menu lateral, que vira barra no topo em ≤ 900 px.
3. **Municípios:** grade de cartões, com um pequeno apresentador das iniciais.

Nenhuma rota, controlador (exceto a leitura de dados que já existe), serviço ou banco muda.

## 2. Análise da arquitetura atual

- `layout.php`: `<header class="topo">` com a marca e `<nav aria-label="Principal">`; `<main class="conteudo">`;
  `<footer class="rodape">` com o link do Guia visual. O item ativo é calculado pelo caminho da URL.
- O teste `testNavigationMarksCurrentSection` lê os links do menu com a expressão
  `<a href="…" aria-current="page">Texto</a>`: **`href` como único atributo antes de `aria-current`, só texto
  dentro**. Por isso os ícones serão desenhados pelo CSS (`a[href="/projetos"]::before`, com máscara SVG embutida),
  e não dentro do link.
- `testNoExternalResources` só aceita `http://www.w3.org/2000/svg` como URL no CSS: os ícones usam
  `data:image/svg+xml` com esse namespace, permitido.
- `testTokenContrast` verifica o contraste dos tokens; os novos valores precisam passar (fundo mais claro só melhora).
- O par "cabeçalho: branco / primária" continua válido: `--cor-primaria` não muda.
- `MunicipioRepository` já devolve `total_projetos`; o controlador já passa `$municipios`.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `public/assets/app.css` | Alterar | Tokens (RF-L04); nova seção "10. LAYOUT COM MENU LATERAL" (RF-L01 a L03); seção "11. MUNICÍPIOS" (RF-M01 a M05). As regras antigas de `.topo` ficam (o teste de nomes legados exige). |
| `templates/layout.php` | Alterar | Menu lateral: `<div class="app">` → `<header class="lateral">` (marca, nav, link do Guia) + `<div class="app-principal">` (main e rodapé). |
| `templates/municipios/index.php` | Alterar (reescrever) | Grade de cartões, menu ⋮, contagem, estado vazio. |
| `src/Presenters/Iniciais.php` | **Criar** | `Iniciais::de('Caxambu do Sul') === 'CS'`, testável (EX-L01). |
| `tests/IniciaisTest.php` | **Criar** | Casos de iniciais. |
| `tests/TelasPadraoTest.php` | Alterar 1 asserção | Hoje espera `<span class="tag info">1 projeto(s)</span>` na lista de municípios; o cartão novo mostra "SC · 1 projeto(s)" (RF-M02). **É um teste meu da fase 2.2, não um teste antigo**; ajusto e registro. |
| `tests/DesignSystemTest.php` | Alterar 1 linha | Limite do CSS 30 → 40 KB (DEC-L3). |
| `tests/MunicipiosTelaTest.php` | **Criar** | Critérios CA-L01 a CA-L03. |
| spec, plano, `docs/HISTORICO.md` | Alterar (no fim) | Registro. |

## 4. Banco de dados

**Nenhuma alteração.**

## 5. Serviços e regras de negócio

Nenhuma alteração. O bloqueio de exclusão com projetos continua no `MunicipioController::excluir()`. A tela só usa
`total_projetos` para desabilitar o botão (DEC-L2).

## 6. Fluxo técnico

**Tokens (RF-L04):**

| Token | Hoje | Novo |
|---|---|---|
| `--fundo` | `#f4f6f8` | `#f6f7f9` |
| `--raio-2` (cartões) | 8 px | 12 px |
| `--raio-1` (botões, campos) | 4 px | 8 px |
| `--sombra-1` | `0 1px 2px rgba(31,41,51,.08)` | `0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.06)` |
| `h1` | 1,875 rem | 1,75 rem, peso 700 e espaçamento de letra levemente negativo (mais moderno) |

**Layout (RF-L01 a L03):**

```html
<div class="app">
  <header class="lateral">
    <a class="marca" href="/">Prestação de Contas</a>
    <nav aria-label="Principal"><a href="/projetos" aria-current="page">Projetos</a> <a href="/municipios">Municípios</a></nav>
    <a class="lateral-rodape" href="/guia-visual">Guia visual</a>
  </header>
  <div class="app-principal">
    <main class="conteudo" id="conteudo" tabindex="-1"> mensagens + conteúdo </main>
    <footer class="rodape"> (igual) </footer>
  </div>
</div>
```

- `.app`: grade `240px 1fr`; `.lateral`: `position: sticky; top: 0; height: 100vh`, fundo branco, borda direita.
- Links do menu: bloco com ícone (`::before` com `mask` SVG, `currentColor`), arredondado; o ativo com fundo
  `--cor-primaria-suave` e texto `--cor-primaria`.
- ≤ 900 px: `.app` vira uma coluna; `.lateral` vira barra horizontal (`height: auto`, `position: static`), sem o
  link do Guia (que continua no rodapé).

**Municípios (RF-M01 a M05):**

```html
<div class="barra projeto-cab"> h1 + p.projeto-sub "2 municípios cadastrados" + a.btn "+ Novo município" </div>
<ul class="grade-cartoes">
  <li class="cartao-mun">
    <div class="cartao-mun-cab"><span class="avatar" aria-hidden="true">CS</span>
      <div><h2 class="cartao-mun-nome">Caxambu do Sul</h2><span class="item-sub">SC · 3 projeto(s)</span></div></div>
    <dl class="cartao-mun-dados"><div><dt>Prefeito</dt><dd>…</dd></div><div><dt>Contador</dt><dd>… <small>CRC</small></dd></div></dl>
    <div class="cartao-mun-rodape">
      <a class="btn secundario pequeno" href="/municipios/1/editar">Editar</a>
      <details class="menu-mais"><summary aria-label="Mais ações para Caxambu do Sul">⋮</summary>
        <div class="menu-mais-painel">
          (sem projetos) <form …/excluir onsubmit="return confirm('Excluir este município?')"> <button class="link perigo">Excluir</button></form>
          (com projetos) <button class="link perigo" type="button" disabled>Excluir</button><small>Possui 3 projeto(s)</small>
        </div></details>
    </div>
  </li>
</ul>
```

- `.grade-cartoes`: `grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))`.
- `.menu-mais-painel`: posicionado abaixo do ⋮ (`position: absolute`, alinhado à direita, sombra), com `z-index`
  abaixo das mensagens fixas.
- O h2 por cartão dá estrutura de títulos para leitores de tela.

## 7. Segurança e permissões

- Todo texto passa por `e()` (nome, prefeito, contador, CRC e o `aria-label` do ⋮).
- CSRF e `confirm()` preservados; o bloqueio de exclusão continua no servidor (o botão desabilitado é só conveniência).

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Ajuste de tokens mudar levemente **todas** as telas | Algum espaçamento/arredondamento estranho em outra tela | Mudanças pequenas e só de valor; conferência visual sua em 2 ou 3 telas além de Municípios. |
| Ícone dentro do link quebrar o teste de navegação | Falha em `testNavigationMarksCurrentSection` | Ícones só no CSS (`::before`); o HTML do link continua só com texto. |
| Menu lateral fixo em tela baixa | Conteúdo do menu cortado | Só 3 itens; `overflow-y: auto` no menu. |
| Menu ⋮ não fecha ao clicar fora | Pequeno incômodo | Limitação aceita na spec; dá para acrescentar depois com poucas linhas de JS, se você pedir. |
| Tabelas largas (balancete) com menos largura útil (menu de 240 px) | Mais rolagem lateral nessas tabelas | Elas já rolam dentro do quadro; o conteúdo usa `--larg-ampla`. Será revisto na fase de cada tela. |
| Sem captura de tela no meu ambiente | Detalhe visual despercebido | Servidor de demonstração (porta 8010) para a sua conferência. |

## 9. Estratégia de implementação

- [x] **Etapa 1: tokens e layout** (CSS seção 10 + `layout.php`) e o limite do CSS. Testes completos.
- [x] **Etapa 2: Municípios** (apresentador e testes de iniciais, template, CSS seção 11, `MunicipiosTelaTest`).
- [x] **Etapa 3: validação.** *(aprovada pelo usuário no navegador)* Suíte completa sem falha nova, HTML bem formado em todas as telas, servidor de
      demonstração com 3 municípios (com e sem projetos e com nome de uma palavra), conferência sua e atualização dos
      documentos.

## 10. Testes e validações

- [ ] `IniciaisTest`: "Caxambu do Sul" → CS; "Chapecó" → CH; "São José dos Cedros" → SC; "Rio do Sul" → RS; espaços
      extras; letras acentuadas em maiúscula ("Érico Veríssimo" → ÉV).
- [ ] `MunicipiosTelaTest`:
  - menu lateral presente (`class="lateral"`) com o item ativo;
  - contagem no singular e no plural;
  - cartões com iniciais, nome, UF e projetos;
  - "Excluir" só dentro de `<details class="menu-mais">`, com `confirm`;
  - desabilitado com "Possui N projeto(s)" quando há projetos;
  - estado vazio;
  - escape de `<script>` no nome.
- [ ] Regressão: todos os testes; únicas alterações registradas: o limite do CSS e a asserção da `TelasPadraoTest` (§3).
- [ ] Manual (você): desktop e 360 px em Municípios e mais 2 telas (Projeto e Balancete); abrir o ⋮ e excluir.

## 11. Critérios de conclusão

CA-L01 a CA-L06 verificados; suíte sem falha nova; documentos atualizados.

## 12. Decisões técnicas pendentes

Nenhuma.

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12).
