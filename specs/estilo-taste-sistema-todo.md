# SPEC — Novo estilo visual em todo o sistema (cor, fonte, botões e microinterações)

**Versão:** 1.2
**Status:** Aprovada em 2026-11-17 — aguardando planejamento técnico (a aprovação da spec não autoriza alterar código)
**Data:** 2026-11-17
**Relacionada a:** `specs/melhoria-visual-fundacao.md` (tokens atuais), `specs/css-por-area.md` (CSS por área) e os redesenhos de
Projetos, Projeto e Municípios. Esta spec **não altera funcionalidades, regras de negócio nem dados**.

---

## 1. Contexto

O sistema tem tokens de design em `public/assets/app.css` (azul `#1f4e79`, fonte do sistema, raios 8 e 12 px) e um CSS por área
(`projetos.css`, `municipios.css`, `balancete.css`). Em 2026-11-17 foi testada a skill `design-taste-frontend` em dois experimentos,
na branch `experimento/taste-skill-municipios`:

1. a tela de **Municípios** recebeu hover (cartão sobe 2 px), botão que afunda ao clicar e título maior;
2. uma **página de apresentação** (`experimento-taste/index.html`) mostrou o estilo completo: verde-azulado `#0d7480`,
   fonte Instrument Sans, botões em pílula, cantos suaves e microinterações.

O usuário **gostou do estilo** e pediu para aplicá-lo ao sistema todo, **tela por tela**.

**Telas do sistema** (13 templates + guia visual): lista de projetos, projeto (tela e formulário), municípios (lista e formulário),
balancete (tela e lançamento), conciliação (tela, nota fiscal e receita), relação de bens (tela e formulário), físico-financeiro
(tela e formulário), junção de documentos, guia visual e erro.

## 2. Objetivo

Aplicar a **cor, a fonte, a forma dos botões e as microinterações** da página de teste a todas as telas do sistema, mantendo o
comportamento exatamente como está.

**A skill em si não é aplicada.** Hero, fotografias, regras de copy de marketing e verificações de altura de seção não servem a um
sistema de trabalho. Aplica-se o **estilo**, não o fluxo da skill.

## 3. Usuários envolvidos

Um único usuário (quem prepara a prestação de contas).

## 4. Requisitos funcionais

### 4.1 Decisões já tomadas pelo usuário (2026-11-17)

- **DEC-E1 Elementos do estilo:** os **quatro**: cor verde-azulada, fonte Instrument Sans, botões em pílula e raios, hover e
  microinterações.
- **DEC-E2 Fonte embutida no projeto:** o arquivo da fonte é baixado para `public/assets/fonts/` (licença OFL) e entregue pelo
  próprio sistema, sem depender de internet nem do Google.
- **DEC-E3 Ritmo:** primeiro a **base global** (tokens em `app.css`), depois **uma tela por vez, com pausa** para o usuário conferir no
  navegador antes da próxima.
- **DEC-E4 Troca de identidade:** o azul `#1f4e79`, registrado como "identidade mantida" na spec de fundação, é **substituído**
  pelo verde-azulado. Esta decisão **revoga** aquela restrição.

### 4.2 Base global (primeira etapa)

- **RF-E01 Cores.** Substituir os tokens de cor pelos da paleta do teste, mantendo os **nomes** dos tokens (assim nenhum template muda):

  | Token | Hoje | Novo |
  |---|---|---|
  | `--cor-primaria` | `#1f4e79` | `#0d7480` |
  | `--cor-primaria-hover` | `#173d5f` | `#0a5a63` |
  | `--cor-primaria-suave` | `#e8f0f7` | `#e3f1f3` |
  | `--foco` | `#1f4e79` | `#0d7480` |
  | `--fundo` | `#f6f7f9` | `#f7f9fa` |
  | `--texto` | `#1f2933` | `#1f3038` |
  | `--texto-suave` | `#52606d` | `#586670` (ajustado: `#5d6b72` dava 4,38:1 sobre o total de tabela) |
  | `--info-bg` / `--info-fg` | `#e8f0f7` / `#1f4e79` | `#e3f1f3` / `#0a5a63` |

  Cores de estado (sucesso, aviso, perigo, neutro) **não mudam**.
- **RF-E02 Contraste.** Todo par texto/fundo continua em **WCAG AA** (4,5:1 para texto, 3:1 para bordas de controle e foco).
  Razões calculadas: primária × branco **5,49:1**; primária × fundo **5,20:1**; primária × suave **4,74:1**; hover × suave **6,83:1**;
  texto × branco **13,65:1**; texto suave × branco **5,92:1**, × total de tabela **4,70:1**. Nota: a primária **cai de 8,66:1 para 5,49:1**; segue acima do mínimo.
- **RF-E03 Fonte.** Instrument Sans (variável) servida por `@font-face` com `font-display: swap`, arquivo em `public/assets/fonts/`,
  com o texto da licença OFL junto. `--fonte` passa a ser `"Instrument Sans", system-ui, …` (a pilha atual vira o fallback).
  Os **PDFs não mudam**: o Dompdf continua com a fonte atual.
- **RF-E04 Botões e raios.** Botões primários e secundários em **pílula** (`--raio-pill`); cartões e painéis com cantos suaves;
  botões pequenos e links de ação seguem o mesmo formato. Altura mínima de toque de 44 px preservada nos controles principais.
- **RF-E05 Microinterações.** Cartão clicável sobe 2 px no hover; botão afunda ao ser pressionado; link desenha o sublinhado.
  Todas as transições ficam **desligadas** sob `prefers-reduced-motion: reduce`.
- **RF-E06 Menu lateral.** Continua **claro** e recolhível (decisão anterior); só recebe as novas cores, fonte e forma dos itens.

### 4.3 Uma tela por vez (etapas seguintes)

Em cada tela: conferir que a base ficou bem aplicada, corrigir o que destoar (espaçamentos, tamanhos de título, estados vazios,
tabelas, chips de situação) e **parar para o usuário conferir**. Ordem proposta (ver DEC-P1):

1. Municípios (lista e formulário) — já tem o experimento aplicado.
2. Lista de projetos.
3. Tela do Projeto e formulário de projeto.
4. Balancete (tela e lançamento).
5. Conciliação (tela, nota fiscal e receita).
6. Físico-financeiro (tela e formulário).
7. Relação de bens (tela e formulário).
8. Junção de documentos.
9. Guia visual e tela de erro.

## 5. Requisitos não funcionais

- **Desempenho:** a fonte é um único arquivo woff2 (cerca de 100 KB), com `font-display: swap`; nenhuma requisição externa.
- **Compatibilidade:** Linux e Windows 11; Chrome e Edge atuais. O sistema continua funcionando **sem a fonte** (fallback).
- **Acessibilidade:** foco visível mantido (anel de 2 px, 3:1), tudo utilizável por teclado, movimento reduzido respeitado.
- **Segurança:** nenhuma requisição a terceiros; o arquivo da fonte vem de fonte conhecida e tem a licença junto.
- **Manutenção:** mudanças de cor e forma ficam nos **tokens**; nenhuma cor nova escrita direto em CSS de tela.

## 6. Regras de negócio

- **RN-E01** Nenhuma regra de negócio, cálculo, texto de formulário, PDF ou dado muda.
- **RN-E02** Os PDFs gerados (balancete, conciliação, relação de bens, físico-financeiro) **não** recebem o novo estilo.
- **RN-E03** Decisões visuais anteriores que **continuam**: menu lateral claro e recolhível; lista de projetos em linhas largas com
  cartão inteiro clicável e sem barra de andamento; tela do Projeto sem o resumo "N de 25"; "Excluir" desabilitado para município
  com projetos; ações secundárias no menu ⋮.

## 7. Fluxos de utilização

### Fluxo principal

1. Aprovada a spec e o plano, o Claude cria a base global e mostra o resultado.
2. O usuário confere o sistema no navegador e aprova ou pede ajustes.
3. O Claude ajusta a tela 1, para; o usuário confere; repete até a tela 9.
4. No fim, relatório de validação e atualização do `docs/HISTORICO.md`.

### Fluxos alternativos

1. Se o usuário não gostar da base, volta-se ao azul pela troca dos tokens da tabela do RF-E01 (reversão simples).
2. O usuário pode pular, reordenar ou descartar telas.

## 8. Exceções e erros

- **EX-01** Fonte não carrega: o sistema usa a pilha de fallback e continua legível; nenhum layout quebra.
- **EX-02** Uma tela depende de uma cor azul escrita direto no CSS (fora dos tokens): é corrigida na etapa da tela, e a ocorrência é
  listada no relatório.
- **EX-03** Contraste abaixo de AA em alguma combinação: ajusta-se o tom; não se aceita a combinação.
- **EX-04** Teste existente que quebre por causa de classe ou token renomeado: corrige-se o teste só se a mudança for intencional e
  aprovada.

## 9. Critérios de aceitação

- [ ] CA-01 Em nenhuma tela aparece mais o azul `#1f4e79` (nem `#173d5f`, `#e8f0f7`), salvo nos PDFs.
- [ ] CA-02 Todas as telas usam Instrument Sans, servida de `/assets/fonts/`, sem requisição externa (conferido na rede do navegador).
- [ ] CA-03 Botões primários e secundários em pílula em todas as telas.
- [ ] CA-04 Hover, pressionar e sublinhado funcionam e **somem** com movimento reduzido.
- [ ] CA-05 Todos os pares de cor em AA; foco visível em todos os controles.
- [ ] CA-06 Suíte PHPUnit sem **novas** falhas em relação à lista de referência anterior.
- [ ] CA-07 Os PDFs saem idênticos aos de antes (conferido por imagem de uma amostra).
- [ ] CA-08 Cada tela foi conferida pelo usuário no navegador antes da próxima.
- [ ] CA-09 Layout usável de 360 px a 1440 px, sem rolagem lateral.

## 10. Restrições técnicas

- PHP + CSS puro; **nenhuma dependência nova** (Composer ou npm). O único arquivo novo de terceiros é a fonte (DEC-E2).
- Limites de tamanho de CSS já testados: `app.css` ≤ 40 KB (hoje cerca de 27 KB); CSS de área ≤ 16 KB.
- Testes existentes verificam que toda `var(--x)` tem definição e que o menu usa `<a aria-current="page">` só com texto: mantidos.
- Não é possível gerar capturas de tela no ambiente do Claude: a conferência visual é do usuário, no navegador.
- O trabalho segue na branch `experimento/taste-skill-municipios` (ou em outra que o usuário indicar); `main` não é tocada.

## 11. Fora do escopo

- Aplicar o fluxo, os scripts ou o gate da skill `design-taste-frontend` ao sistema.
- Fotografias, hero, faixa escura, faixas deslizantes e efeitos da página de teste.
- Modo escuro.
- Novos recursos, campos, telas ou regras de negócio.
- Estilo dos PDFs.
- Logotipo novo (ver DEC-P2).
- Commit e push (só quando o usuário pedir).

## 12. Decisões pendentes

- [x] DEC-P1 **Ordem das telas:** aprovada como está no item 4.3 (2026-11-17).
- [x] DEC-P2 **Marca da lateral:** mantém só o texto "Prestação de Contas"; sem ícone novo (2026-11-17).

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação inicial, após a descoberta (DEC-E1 a DEC-E4) | 2026-11-17 |
| 1.1 | Spec aprovada; DEC-P1 (ordem das telas) e DEC-P2 (marca só com texto) resolvidas | 2026-11-17 |
| 1.2 | `--texto-suave` ajustado de `#5d6b72` para `#586670` (EX-03: contraste 4,38:1 sobre o total de tabela) | 2026-11-17 |
