
# SPEC — Melhoria visual da interface: Fundação visual (Fase 1)

**Versão:** 1.0
**Status:** Aprovada (2026-11-12)
**Data:** 2026-11-12
**Relacionada a:** `specs/prestacao-de-contas.md` (o sistema); esta spec não altera regras de negócio.

---

## 1. Contexto

O sistema (PHP, local, uso por uma pessoa) tem 14 templates de tela e um único `app.css` de
61 linhas, criado de forma incremental ao longo das etapas de funcionalidade. O levantamento visual
(2026-11-12) concluiu que a interface é funcional e sóbria, mas **não tem sistema de design**:
cores e espaçamentos soltos, 9 estilos inline, nenhuma regra responsiva (`@media`), nenhum estado
de foco/hover, campos de formulário com baixo contraste (borda a 1,88:1), mensagens que podem sair
de vista após um redirecionamento com âncora, e o mesmo componente (botão, tabela, aviso) com
aparências diferentes conforme a tela.

A melhoria visual será feita **em fases**: primeiro a **fundação** (esta spec), depois **uma tela
por vez**, cada uma com proposta, aprovação, implementação e validação.

## 2. Objetivo

Criar a base visual compartilhada — tokens, componentes e layout global — que torne a interface
moderna, profissional, consistente, acessível e utilizável em telas pequenas, **sem alterar
funcionalidades, regras de negócio, rotas, integrações nem comportamento**, e preservando a
identidade atual (azul `#1f4e79`, fundo claro, cartões brancos, fonte do sistema).

**Problema resolvido:** sem essa base, melhorar tela por tela produziria estilos divergentes para
os mesmos componentes e retrabalho a cada fase.

## 3. Usuários envolvidos

Um único usuário (quem prepara a prestação de contas), em computador de mesa; uso eventual em
tela menor deve funcionar sem rolagem horizontal da página.

## 4. Requisitos funcionais

> "Funcionais" aqui significam comportamentos visuais/de interface. Nenhum altera dados ou regras.

**Estratégia de compatibilidade (RF-V00):** todos os nomes de classe CSS e atributos usados hoje
(`.btn`, `.tag`, `.tag.ok`, `.tag.vazio`, `.tag.aviso-tag`, `.form`, `.caixa`, `.aviso`, `.msg`,
`.rolagem`, `.num`, `.acoes`, `button.link`, `draggable="true"`, etc.) **continuam existindo e
funcionando**; a fundação os *refina*, não os renomeia. Assim, as telas já melhoram de forma
consistente, e a reestruturação de cada uma fica para sua fase.

- RF-V01 **Tokens** (variáveis CSS em `:root`): paleta, escala de espaçamento, raios, sombras,
  escala tipográfica e larguras (seção 6).
- RF-V02 **Tipografia:** manter a fonte do sistema (sem fontes externas); escala definida; títulos
  com hierarquia clara; rótulos de formulário em peso médio (não negrito pesado em tudo).
- RF-V03 **Botões:** variantes primário, secundário (contorno), texto/discreto (substitui o link
  sublinhado de ação de linha) e perigo (sólido e discreto); tamanhos padrão e pequeno; estados
  hover, active, foco visível e desabilitado.
- RF-V04 **Formulários:** campos com altura e preenchimento consistentes; borda com contraste
  ≥ 3:1; foco com anel visível; indicação visual de campo obrigatório; texto de ajuda padronizado;
  `fieldset`/`legend` estilizados; agrupamento em grade responsiva (colunas que empilham).
- RF-V05 **Tabelas:** cabeçalho distinto, realce de linha ao passar o mouse, alinhamento numérico à
  direita com algarismos tabulares, rodapé de totais destacado, densidade compacta; toda tabela
  dentro de um contêiner de rolagem horizontal (`.rolagem`), para não estourar a página em tela
  pequena.
- RF-V06 **Etiquetas (tags/badges):** variantes neutra, sucesso, aviso, perigo e informação, sempre
  com texto (nunca só cor).
- RF-V07 **Mensagens (alertas):** variantes sucesso, erro, aviso e informação, com ícone; a área de
  mensagens fica **fixa no topo da janela** (sticky), de modo que continua visível mesmo quando o
  navegador rola até uma âncora após o redirecionamento; botão de fechar (JavaScript simples, com
  degradação: sem JS a mensagem permanece e não atrapalha). Hoje o amarelo "aviso" serve a 3 usos;
  passam a existir estilos distintos: **informação**, **estado vazio** e **alerta**.
- RF-V08 **Superfícies (cartões):** `.caixa`, `.form` e `.dados` compartilham borda, raio, sombra e
  espaçamento; larguras máximas coerentes entre formulário e tabela na mesma tela.
- RF-V09 **Layout global:** cabeçalho com marca e navegação com **estado ativo**; contêiner com
  largura padrão (1100 px) e modificador largo (1280 px) disponível para telas de muitos dados;
  rodapé discreto; *link* "Ir para o conteúdo" para teclado.
- RF-V10 **Componentes novos, disponíveis mas ainda não aplicados às telas:** trilha de navegação
  (breadcrumb), faixa de contexto do projeto (município + objeto), estado vazio, cartão de
  indicador (KPI) e barra de página (título + ações). As telas os adotarão em suas fases.
- RF-V11 **Responsividade:** pontos de quebra definidos (≤ 640 px e ≤ 1024 px); contêineres com
  margens laterais adequadas; grupos de ações e listas (`.acoes`, `.lista-arquivos li`) quebram
  linha; formulários empilham; áreas de toque ≥ 40 px em tela pequena.
- RF-V12 **Acessibilidade:** foco visível em todo elemento interativo; contraste de texto ≥ 4,5:1 e
  de bordas/anéis de componentes ≥ 3:1; respeito a `prefers-reduced-motion`; informação nunca só
  por cor.
- RF-V13 **Ícone da aba (favicon)** em SVG, para eliminar a requisição que hoje resulta em 404.
- RF-V14 **Página de erro** (404/403/419/500) com o mesmo tratamento visual dos demais estados
  (conteúdo centralizado e ação de voltar), sem alterar os códigos de status nem as mensagens.

## 5. Requisitos não funcionais

- **Dependências:** nenhuma nova. Apenas HTML, CSS e JavaScript puros; ícones em SVG embutido;
  nenhuma fonte, biblioteca nem CDN (o sistema é local e deve funcionar sem internet).
- **Desempenho:** CSS total ≤ 30 KB sem compressão; JavaScript ≤ 3 KB; sem bloqueio de renderização
  além do CSS.
- **Compatibilidade:** navegadores atuais (Chrome, Edge e Firefox das duas últimas versões). Podem
  ser usados recursos modernos como `:focus-visible` e `:has()` (ver DEC-V4).
- **Segurança:** nenhuma mudança; a proteção CSRF, o escape de saída (`e()`) e as demais proteções
  permanecem. Nenhum HTML novo pode introduzir saída sem escape.
- **Manutenibilidade:** tokens e componentes documentados em comentário no topo do CSS; os estilos
  inline que existirem nos templates não são tocados nesta fase (serão removidos em cada tela).

## 6. Tokens propostos (contrastes verificados por cálculo WCAG)

**Cores — mantêm a identidade atual**

| Token | Valor | Uso | Contraste verificado |
|---|---|---|---|
| `--cor-primaria` | `#1f4e79` (atual) | botões, links, cabeçalho, foco | 8,66:1 c/ branco |
| `--cor-primaria-hover` | `#173d5f` | hover/ativo | 11,23:1 c/ branco |
| `--cor-primaria-suave` | `#e8f0f7` | fundos de destaque | texto primário 7,52:1 |
| `--fundo` | `#f4f6f8` (atual) | fundo da página | — |
| `--superficie` | `#ffffff` | cartões, tabelas | — |
| `--texto` | `#1f2933` | texto principal | 13,62:1 / fundo; 14,76:1 / branco |
| `--texto-suave` | `#52606d` | texto secundário | 5,96:1 / fundo; 6,46:1 / branco |
| `--borda-suave` | `#d5dbe1` (atual) | divisórias decorativas | decorativa |
| `--borda-campo` | `#7b8794` | bordas de campos e controles | 3,38:1 / fundo; 3,66:1 / branco (hoje 1,88:1) |
| `--foco` | `#1f4e79`, anel 2 px com recuo de 2 px | foco visível | 8,00:1 |

**Cores semânticas (fundo / texto), uso restrito a estado**

| Estado | Fundo | Texto | Contraste |
|---|---|---|---|
| Sucesso | `#e7f5ea` | `#166534` | 6,33:1 |
| Aviso | `#fff3cd` | `#7a5b00` | 5,70:1 |
| Perigo | `#fdecea` | `#9b1c1c` | 7,13:1 |
| Perigo sólido (botão) | `#b3261e` | `#ffffff` | 6,54:1 |
| Informação | `#e8f0f7` | `#1f4e79` | 7,52:1 |
| Neutro (tag) | `#e4e7eb` | `#3e4c59` | 7,10:1 |

**Escalas**

- Espaçamento (base 4 px): 4, 8, 12, 16, 24, 32, 48.
- Raios: 4 px (campos, botões), 8 px (cartões), 999 px (etiquetas).
- Sombras: sutil (cartões) e média (mensagens fixas); sem sombras decorativas.
- Tipografia: 12, 14, 16, 18, 20, 24, 30 px; peso 400/500/600/700; corpo 16 px, tabelas e
  formulários densos 14 px; altura de linha 1,5 (corpo) e 1,25 (títulos).
- Larguras do contêiner: 1100 px (padrão) e 1280 px (largo).

## 7. Regras de negócio

Nenhuma é afetada. Regras desta melhoria:

- RN-V01 Nenhum comportamento existente pode mudar: rotas, métodos, campos enviados, validações,
  mensagens de texto, cálculos, geração de PDF e códigos de resposta permanecem idênticos.
- RN-V02 Os PDFs (`templates/pdf/*`) e seus estilos **não fazem parte** desta melhoria.
- RN-V03 Funcionalidades de acessibilidade já existentes (setas ↑↓ sem JavaScript, arraste e solte
  como realce progressivo) devem ser preservadas.
- RN-V04 Texto de interface continua em português do Brasil; nenhuma mensagem é reescrita sem
  aprovação (esta fase não altera textos, exceto rótulos de acessibilidade novos).

## 8. Fluxos de utilização

### Fluxo principal (desenvolvimento)

1. Aprovar esta especificação.
2. Elaborar o plano técnico (arquivos, ordem, riscos, validação) e aprová-lo.
3. Autorização explícita para implementar.
4. Implementar a fundação em etapas pequenas, com checkpoints.
5. Validar (testes automáticos + conferência visual) e entregar relatório.
6. Seguir para a Fase 2: primeira tela, com nova rodada de proposta e aprovação.

### Fluxos alternativos

1. Se algo na fundação quebrar um comportamento, reverter o ponto e ajustar antes de seguir.
2. Se uma decisão pendente (seção 12) mudar o escopo, atualizar esta spec e registrar no histórico.

## 9. Exceções e erros

- EX-V01 Navegador sem JavaScript: mensagens permanecem visíveis (sem botão de fechar funcional) e
  todos os fluxos continuam operáveis.
- EX-V02 Navegador antigo sem suporte a um recurso moderno (ex.: `:has()`): o recurso degrada de
  forma silenciosa (ex.: o asterisco de obrigatório não aparece), sem quebrar o layout.
- EX-V03 Mensagem de erro de validação vinda do servidor: continua sendo exibida como hoje (varias
  mensagens empilhadas), apenas com o novo estilo.

## 10. Critérios de aceitação

- [ ] CA-V01 Os 200 testes automáticos existentes passam **sem alteração** (ou, se algum depender
      de trecho de HTML, a alteração é mínima, justificada e registrada).
- [ ] CA-V02 Todo par texto/fundo da paleta tem contraste ≥ 4,5:1 e toda borda/anel de componente
      ≥ 3:1, conferido por script de cálculo (relatório anexado ao fim da fase).
- [ ] CA-V03 Nenhuma tela provoca rolagem horizontal **da página** com 360 px de largura; tabelas
      rolam dentro do próprio contêiner. (Conferência visual no navegador — DEC-V3.)
- [ ] CA-V04 Todo elemento interativo mostra foco visível ao navegar por Tab.
- [ ] CA-V05 Após um envio/salvamento que redireciona para uma âncora, a mensagem continua visível
      no topo da janela e pode ser fechada.
- [ ] CA-V06 O mesmo componente (botão, etiqueta, mensagem, tabela, cartão, campo) tem a mesma
      aparência em todas as telas.
- [ ] CA-V07 O favicon é servido (sem 404) e as páginas de erro seguem o novo visual.
- [ ] CA-V08 `templates/pdf/*` e os testes de PDF permanecem inalterados e passando.
- [ ] CA-V09 Nenhuma dependência nova foi adicionada; CSS ≤ 30 KB e JS ≤ 3 KB.

## 11. Restrições técnicas

- Sem novas dependências, CDNs ou fontes externas (`CLAUDE.md` exige autorização para dependências).
- Os templates continuam em PHP; o escape de saída com `e()` é obrigatório em todo HTML novo.
- Alterações de markup limitam-se ao mínimo para a fundação (layout global e envolver tabelas em
  `.rolagem`); o restante do markup de cada tela é reestruturado na fase da própria tela.
- Ambiente de desenvolvimento não consegue gerar capturas de tela automaticamente (Chrome/Firefox
  sem interface não funcionam aqui); a conferência visual depende do navegador do usuário.

## 12. Fora do escopo

- Redesenho/reestruturação de cada tela (Fase 2 em diante, uma por vez).
- Qualquer alteração em PDFs, regras de negócio, banco de dados, rotas ou textos de negócio.
- Modo escuro, impressão da interface e internacionalização.
- Logotipo ou marca gráfica nova (identidade atual preservada).
- Bibliotecas de componentes, frameworks CSS e ícones externos.

## 13. Decisões pendentes

- [ ] DEC-V1 **Itens opcionais que acrescentam comportamento novo** (todos apenas de apresentação):
      contador "n de 25 documentos preenchidos", filtro por situação, janela de confirmação própria
      (no lugar da nativa do navegador) e envio automático ao escolher o arquivo.
      *Padrão se não houver resposta: **fora** da fundação e das fases.*
- [ ] DEC-V2 **Indicador de processamento** (desabilitar o botão e mostrar "Gerando…" ao enviar
      formulários lentos, evitando duplo clique). *Padrão: fora.*
- [ ] DEC-V3 **Validação visual:** o desenvolvimento não consegue renderizar telas. *Premissa
      adotada: o usuário confere cada etapa no navegador (ou envia capturas); os testes
      automáticos garantem apenas o comportamento.*
- [ ] DEC-V4 **Navegadores-alvo:** Chrome/Edge/Firefox atuais (permite `:focus-visible`, `:has()`).
      *Padrão: este.*
- [ ] DEC-V5 **Largura do contêiner:** padrão 1100 px e largo 1280 px (este para balancete,
      físico-financeiro e conciliação, decidido na fase de cada tela). *Padrão: este.*

## 14. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação inicial, a partir do levantamento visual e da decisão de começar pela fundação | 2026-11-12 |
| 1.1 | Especificação aprovada pelo usuário; DEC-V1 a DEC-V5 seguem com o padrão indicado | 2026-11-12 |
| 1.2 | Fundação implementada. Acrescentada a página `/guia-visual` (somente leitura), aprovada pelo usuário como extensão do escopo (DT-V1) para viabilizar a conferência visual | 2026-11-12 |
