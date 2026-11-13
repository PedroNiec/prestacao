# PLAN — CSS dividido por área (base + um arquivo por área)

**Versão:** 1.0
**Status:** Implementado (2026-11-13)
**SPEC relacionada:** `specs/css-por-area.md` (v1.0, aprovada em 2026-11-13, com DEC-01, DEC-02 e DEC-03 recomendadas)
**Data:** 2026-11-13

---

## 1. Resumo da implementação

1. O `View::render` passa a deduzir a **área** pelo primeiro segmento do nome do template (`projetos/index` → `projetos`; `guia_visual` → `guia-visual`)
   e, se existir `public/assets/<área>.css`, entrega o nome ao layout, que acrescenta um segundo `<link>` depois do `app.css`. **Nenhum controller muda.**
2. As regras são **movidas** (não reescritas) do `app.css` para os arquivos de área, por um script que classifica cada regra pelo uso das classes
   nos templates. Regras cujas classes aparecem em mais de uma área ficam na base.
3. Os testes de design passam a ler o conjunto de arquivos e ganham verificações por tela.
4. A prova de "nenhuma mudança visual" é feita em duas camadas: o **estilo computado de cada elemento** de cada tela (antes × depois, por hash)
   e capturas de tela nas telas principais, em tela larga e estreita.

## 2. Análise da arquitetura atual

- `templates/layout.php` carrega só `/assets/app.css`; `View::render` renderiza o template primeiro (`$content`) e depois o layout.
- Os controllers chamam `View::render('area/tela', ...)` em ~26 pontos; o nome do template já carrega a área (`projetos/`, `municipios/`, `balancete/`,
  `conciliacao/`, `fisico/`, `bens/`, `documentos/`, `partes/`, `guia_visual`, `erro`).
- `DesignSystemTest` lê `public/assets/app.css` em `css()` e dela derivam: tokens e contraste, tokens obrigatórios, classes definidas × usadas,
  classes legadas, `var(--x)` sem definição, chaves balanceadas, `!important`, limite de tamanho e recursos externos.
- Levantamento de uso por seção (spec §1): só as seções 7 (guia visual), 12 (lista de Projetos), 13 e 14 (balancete) e parte da 11 (Municípios)
  são de uma área; as seções 8 e 9 e o restante são compartilhados.
- Atenção a **classes compartilhadas definidas dentro de uma seção "de área"**: por exemplo `.menu-mais*` nasceu na seção de Municípios e é usado
  também pelo balancete; ela fica na **base**. O script de classificação trata isso pelo uso, não pelo nome da seção.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `public/assets/app.css` | Alterar | Fica só com o compartilhado (~31 KB) |
| `public/assets/projetos.css` | Criar | Lista de Projetos (seção 12) e o que mais for só da área |
| `public/assets/municipios.css` | Criar | Regras exclusivas de Municípios (seção 11, menos o compartilhado) |
| `public/assets/balancete.css` | Criar | Seções 13 e 14 (grid e formulário de lançamentos) |
| `public/assets/guia-visual.css` | Criar | Seção 7 |
| `src/Core/View.php` | Alterar | `areaCss()` por convenção do nome do template; entrega `cssArea` ao layout |
| `templates/layout.php` | Alterar | `<link>` opcional do CSS da área após o `app.css` |
| `tests/DesignSystemTest.php` | Alterar | Lê o conjunto de arquivos; limites por arquivo; testes por tela; lista de classes legadas sem as removidas |
| `tests/CssPorAreaTest.php` | Criar | Teste HTTP: cada tela carrega a base e só o CSS da própria área |
| `templates/guia_visual.php` | Alterar (se preciso) | Texto cita "tokens em app.css"; continua verdadeiro, sem mudança prevista |
| `docs/HISTORICO.md`, `README.md` (se citar o CSS), spec e plano | Alterar | Documentação sincronizada |

Nenhum controller, serviço, repositório, template de tela ou migração é alterado.

## 4. Banco de dados

Sem alterações.

## 5. Serviços e regras de negócio

Não há regra de negócio. Regras técnicas:

- **Convenção de área** (`View::areaCss(string $template): ?string`): primeiro segmento do nome, `_` → `-`; só devolve o nome se `public/assets/<nome>.css`
  existir. `partes/*`, `documentos/*`, `conciliacao/*`, `fisico/*`, `bens/*`, `erro` e `layout` não têm arquivo de área hoje, então carregam só a base
  (e passam a carregar o seu quando o arquivo nascer, sem mexer em código).
- **Cascata:** o arquivo de área vem **depois** da base. Ao mover regras de áreas para o fim, só muda a ordem em relação a regras da base com a mesma
  especificidade e a mesma propriedade. O script lista esses casos e a verificação de estilo computado (Etapa 5) os pega.
- **Tokens (`:root`) só na base** (RN-02).
- **Limites** (DEC-01): base 40 KB e 16 KB por arquivo de área; JS global segue em 3 KB.

## 6. Fluxo técnico

Requisição → controller → `View::render('projetos/index', …)` → `capture` do template → `areaCss('projetos/index')` = `projetos` → layout recebe `cssArea` →
`<link app.css>` + `<link projetos.css>`. Para `conciliacao/index`, `areaCss` devolve `null` e o layout emite só o `app.css`.

## 7. Segurança e permissões

- O nome do arquivo de área vem **do nome do template** (já validado por regex em `capture`) e só é usado se o arquivo existir; nunca de entrada do usuário.
- Valor escapado com `e()` no `href`; nenhum recurso externo (teste existente passa a cobrir todos os CSS).

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Regra movida para o arquivo errado (classe usada por outra área) | Tela perde estilo | Script classifica por uso real; teste "toda classe usada pelos templates de uma área está na base ou no CSS dela" e teste inverso (classe de arquivo de área não é usada fora da área) |
| Mudança de ordem na cascata | Diferença visual sutil | Verificação por **hash do estilo computado** de todos os elementos, antes × depois, nas telas e em 2 larguras; capturas conferidas por mim |
| Regra com seletor misto (`.lanc .menu-mais-painel`) | Ficaria sem a parte base | Vai para a área do seletor mais específico (`.lanc` → balancete); o componente base permanece na base; a ordem (base depois área) preserva o efeito |
| Cache do navegador com `app.css` antigo | Estilo duplicado/desencontrado até recarregar | Inofensivo (o antigo contém tudo); recarregar resolve. Opcional: `?v=` com a data do arquivo (DT-2) |
| Testes de design acoplados ao arquivo único | Falhas em cascata | Trocar `css()` por união dos arquivos, e `cssBase()` onde só a base vale (tokens, contraste, tamanho) |
| Classes legadas na lista de compatibilidade (`form-larga`) | Teste falha ao remover as 5 classes sem uso (DEC-03) | Atualizar a lista de propósito no mesmo passo |
| PDF (Dompdf) | Nenhum | Os PDFs têm CSS próprio em `templates/pdf/` |

## 9. Estratégia de implementação

- [x] **Etapa 0 — linha de base:** com o servidor atual, registrar o hash do estilo computado (e capturas das telas principais) de cada tela, em tela larga e estreita:
      lista e tela de Projeto, novo projeto, Municípios (lista e formulário), balancete (e edição), conciliação (e formulário de receita), físico-financeiro,
      relação de bens, junção, guia visual e página de erro. Guardar na pasta de rascunhos da sessão.
- [x] **Etapa 1 — mecanismo:** `View::areaCss` + `layout.php` + testes do mecanismo (`CssPorAreaTest`), ainda com os arquivos de área vazios/inexistentes (nada muda na tela).
- [x] **Etapa 2 — classificação:** script que lê o `app.css`, separa as regras por seção, calcula o uso por área e produz o mapa (regra → destino) e a lista de
      conflitos de cascata. Conferir o mapa comigo antes de mover.
- [x] **Etapa 3 — mover as regras:** gerar `app.css` (base) e os 4 arquivos de área a partir do mapa, preservando cabeçalhos, comentários e a ordem; remover as 5 classes sem uso.
- [x] **Etapa 4 — testes de design:** `css()` = união; `cssBase()`; limites por arquivo; classes usadas por área × definidas (base ∪ área); teste inverso;
      lista de classes legadas atualizada; recursos externos em todos os CSS.
- [x] **Etapa 5 — validação:** suíte completa; repetir a Etapa 0 e comparar os hashes (diferença = investigar); capturas lado a lado das telas principais.
- [x] **Etapa 6 — documentação:** `HISTORICO.md` (nova estrutura de CSS e limites), README se citar o CSS, status da spec e do plano.

Pontos de pausa: após a Etapa 2 (mapa para conferência) e ao fim da Etapa 5 (relatório), antes de qualquer commit.

## 10. Testes e validações

- [x] **Funcional (HTTP):** `/projetos` carrega `app.css` + `projetos.css`; `/municipios`, balancete e guia visual idem com o próprio arquivo; conciliação, físico-financeiro, bens, junção e erro carregam só `app.css`; nenhuma tela carrega CSS de outra área.
- [x] **Regras:** `areaCss` só devolve área existente; nome inválido não gera `<link>`.
- [x] **Integridade do CSS:** `var(--x)` sem definição (no conjunto), chaves e parênteses balanceados, sem `!important` fora do movimento reduzido, sem `@import`/`@font-face`, sem `:root` em arquivo de área.
- [x] **Consistência por tela:** toda classe usada por templates de uma área está na base ∪ CSS da área; classe definida em arquivo de área não é usada fora da área.
- [x] **Tamanho:** base ≤ 40 KB; cada área ≤ 16 KB; JS ≤ 3 KB.
- [x] **Regressão visual:** hash do estilo computado antes × depois e capturas, em 2 larguras (CA-05).
- [x] **Regressão geral:** suíte completa (hoje 327).

## 11. Critérios de conclusão

CA-01 a CA-07 da spec atendidos; hashes de estilo computado idênticos antes e depois (ou diferenças explicadas e aprovadas); suíte completa passando;
`app.css` com folga na base; documentação sincronizada; relatório final entregue.

## 12. Decisões técnicas pendentes

- [x] DT-1: Área deduzida **pela convenção do nome do template** (sem tocar nos controllers). Recomendado. Alternativa: variável `css` passada por cada controller (~26 pontos).
- [x] DT-2: **Não** acrescentar `?v=` (versão) ao `<link>` nesta mudança, para manter o escopo (cache antigo é inofensivo). Recomendado.
- [x] DT-3: Verificação visual por **hash do estilo computado + capturas** (usa o Chrome do usuário via extensão, como nas outras conferências). Recomendado.

## 13. Aprovação

- [x] Especificação aprovada (2026-11-13).
- [x] Planejamento aprovado.
- [x] Autorização para implementação.
