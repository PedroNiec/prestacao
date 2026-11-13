# SPEC — Melhoria visual: demais telas (Fase 2.2)

**Versão:** 1.2
**Status:** Implementada (2026-11-12) — aguardando conferência visual do usuário
**Data:** 2026-11-12
**Relacionada a:** `specs/melhoria-visual-fundacao.md` (fundação, implementada),
`specs/melhoria-visual-tela-projeto.md` (tela do Projeto, implementada; é o **modelo** desta fase) e
`specs/prestacao-de-contas.md` (sistema). Esta spec **não altera funcionalidades nem regras de negócio**.

---

## 1. Contexto

A tela do Projeto foi reorganizada e aprovada pelo usuário. As demais telas ainda têm o visual "de etapa de
desenvolvimento": funcionam, mas cada uma tem um jeito próprio de mostrar navegação, situação do PDF,
listas vazias e formulários.

**Levantamento (2026-11-12), 13 telas:**

| Tela | Template |
|---|---|
| Lista de projetos | `projetos/index.php` |
| Novo/editar projeto | `projetos/form.php` |
| Lista de municípios | `municipios/index.php` |
| Novo/editar município | `municipios/form.php` |
| Balancete (01) e lançamento | `balancete/index.php`, `balancete/form.php` |
| Físico-financeiro (22) e meta/etapa | `fisico/index.php`, `fisico/form.php` |
| Conciliação (15) e nota fiscal | `conciliacao/index.php`, `conciliacao/nota_form.php` |
| Junção (02–07, 13, 14) | `documentos/juncao.php` |
| Erro (404 etc.) | `erro.php` |

**Problemas observados**

1. **Navegação inconsistente:** só a tela do Projeto tem trilha; as internas usam "← Voltar ao projeto #1", que
   mostra o número em vez do nome e não diz em que projeto a pessoa está.
2. **Amarelo para tudo:** a caixa `aviso` (amarela, de alerta) é usada em 7 lugares, inclusive em listas vazias
   ("Nenhum lançamento ainda"). O alerta verdadeiro ("a conciliação não fechou") não se destaca.
3. **Situação do PDF desigual:** balancete, físico-financeiro, conciliação e junção mostram a situação do PDF com
   pequenas diferenças de texto e de layout.
4. **Números importantes enterrados:** totais e saldos aparecem só no rodapé das tabelas (balancete) ou numa tabela
   pequena (resumo da conciliação).
5. **Formulários "crus":** sem cartão nem cabeçalho de contexto; "Cancelar" é um link solto ao lado de "Salvar".
6. **Listas de projetos e municípios:** tabelas simples; "Abrir" é um link pequeno no fim da linha; no celular, rolam
   de lado.
7. **9 estilos inline** (`style="..."`) nos templates.

## 2. Objetivo

Deixar **todas as telas** com a mesma cara e a mesma organização da tela do Projeto, mais bonitas e
consistentes, **sem mudar nenhum comportamento**, nenhuma regra e nenhum dado.

## 3. Usuários envolvidos

Um único usuário (quem prepara a prestação de contas).

## 4. Requisitos funcionais (proposta visual)

### 4.1 Padrões que valem para todas as telas

- **RF-T01 Trilha de navegação** em todas as telas internas, no lugar de "← Voltar…":
  `Projetos › <objeto do projeto> › 01 Balancete › Novo lançamento` e `Municípios › Novo município`.
  O objeto aparece **resumido** (cortado com "…" se for longo, com o texto inteiro ao passar o mouse). O último
  item não é link.
- **RF-T02 Cabeçalho padrão:** título (h1), subtítulo de contexto (por exemplo, nome do projeto e município nas
  telas de documento) e a **ação principal** à direita (como "Novo lançamento").
- **RF-T03 Cartão "PDF do documento"**, igual nas 4 telas que geram PDF (balancete, físico-financeiro, conciliação e
  junção): etiqueta da situação + frase curta + **Gerar PDF** + Ver · Baixar. Textos das etiquetas e das frases
  **mantidos**.
- **RF-T04 Estados vazios** com o componente neutro `estado-vazio` (título, frase e, quando houver, o botão da ação),
  no lugar da caixa amarela. **A caixa amarela fica só para alertas de verdade.** Textos mantidos.
- **RF-T05 Formulários em cartão:** seções com título, dicas em texto de apoio e botões no rodapé: **Salvar**
  (principal) e **Cancelar** (botão secundário, não mais um link solto).
- **RF-T06 Ações de linha nas tabelas** (Editar · ↑ ↓ · Excluir) com o mesmo visual em todas as listas: Editar como
  link, setas agrupadas e compactas, Excluir discreto em vermelho, como na tela do Projeto.
- **RF-T07 Sem estilos inline:** os 9 `style="..."` viram classes no CSS.

### 4.2 Por tela

- **RF-T10 Lista de projetos:** cada projeto em destaque: o **objeto é o link** para abrir; município e processo
  aparecem como informação secundária. No celular, cada projeto vira um cartão. A lista vazia usa o estado vazio com o
  botão "Novo projeto" (ou "Cadastrar município", se ainda não houver município).
- **RF-T11 Lista de municípios:** mesmo padrão. Prefeito e contador como secundários, nº de projetos como etiqueta
  neutra, e Editar · Excluir discretos. Cartões no celular.
- **RF-T12 Formulários de projeto e de município:** trilha, cartão e seções ("Identificação", "Local, data e
  assinaturas" / "Signatários padrão"). Campos, nomes, validações e o preenchimento automático (`projeto-form.js`)
  **inalterados**.
- **RF-T13 Balancete:** cabeçalho com o projeto; cartão do PDF; **3 indicadores** (Receita total, Despesa total e
  Saldo) no topo, usando o componente `kpis` que já existe e os totais já calculados; tabela de lançamentos como hoje
  (rola de lado no celular, porque são 12 colunas); "Totais por origem" num cartão.
- **RF-T14 Formulário de lançamento:** trilha e cartão. As seções "Tipo e valor", "Origem e fonte" e "Documentos"
  agrupam os campos atuais. "Salvar e adicionar outro" é mantido.
- **RF-T15 Físico-financeiro:** cabeçalho, cartão do PDF, as tabelas "Físico" e "Financeiro" como seções com título; o
  aviso de unidades diferentes no rodapé é mantido.
- **RF-T16 Formulário de meta/etapa:** trilha e cartão (as seções atuais são mantidas).
- **RF-T17 Conciliação:** cabeçalho; cartão do PDF; o **resumo vira indicadores** (Receitas, Despesas, Saldo, Saldo
  bancário e Conciliação). O indicador "Conciliação" fica **verde com o texto "fechou"** quando é zero e **vermelho com
  o texto "não fechou"** quando não é (cor e texto, nunca só cor). O aviso amarelo atual ("não fechou… devoluções não
  entram") **continua** logo abaixo. As seções 1, 2 e 3 viram cartões numerados, com atalhos para elas no topo
  (`#receitas`, `#notas`, `#dados`, âncoras que já existem).
- **RF-T18 Formulário de nota fiscal:** trilha e cartão.
- **RF-T19 Junção:** cabeçalho; cartão do PDF; a lista de arquivos com o visual atual, apenas alinhado ao padrão; a
  área "Adicionar PDFs" num cartão logo **abaixo da lista**. Arrastar, setas e remover **inalterados**.
- **RF-T20 Página de erro:** mantém o componente atual e o destino `/`. Só muda o texto do botão para "Voltar aos
  projetos" se a DEC-T4 for aprovada.

### Esboço: tela de documento (balancete)

```
Projetos › Aquisição de um veículo ônibus… › 01 Balancete
┌───────────────────────────────────────────────────────────────────────────┐
│ 01 — Balancete                                       [Novo lançamento]    │
│ Aquisição de um veículo ônibus… · Caxambu do Sul - SC                     │
└───────────────────────────────────────────────────────────────────────────┘
┌ PDF do documento ─────────────────────────────────────────────────────────┐
│ [Desatualizado] Os lançamentos mudaram desde a última geração.            │
│ [Gerar PDF]  Ver  Baixar                                                  │
└───────────────────────────────────────────────────────────────────────────┘
┌ Receita total ──────┐ ┌ Despesa total ──────┐ ┌ Saldo ──────────────────┐
│ 497.152,49          │ │ 491.186,66          │ │ 5.965,83                │
└─────────────────────┘ └─────────────────────┘ └─────────────────────────┘
Lançamentos (6)
 Parc. | Descrição | … | Receita | Despesa |  Editar ↑↓ Excluir
```

### Esboço: lista de projetos

```
Projetos                                                   [Novo projeto]
┌───────────────────────────────────────────────────────────────────────────┐
│ AQUISIÇÃO DE UM VEÍCULO ÔNIBUS PARA O TRANSPORTE ESCOLAR          (link)  │
│ Caxambu do Sul - SC · PROCESSO SCC 7909/2025 · Projeto #1                 │
├───────────────────────────────────────────────────────────────────────────┤
│ PAVIMENTAÇÃO DA RUA …                                                     │
│ Caxambu do Sul - SC · — · Projeto #2                                      │
└───────────────────────────────────────────────────────────────────────────┘
```

## 5. Requisitos não funcionais

- **Só HTML e CSS:** nenhum JavaScript novo e nenhuma dependência. Os scripts existentes (`juncao.js`,
  `projeto-form.js`, `lancamento-form.js`) continuam funcionando, com os mesmos `id`s e atributos.
- **Acessibilidade:** trilha em `<nav aria-label>`; foco visível; informação nunca só por cor; rótulos dos campos
  preservados.
- **Celular (≤ 640 px):** sem rolagem horizontal da página; as listas de projetos e municípios viram cartões; as tabelas
  numéricas largas (balancete, físico-financeiro, conciliação) **continuam rolando dentro do próprio quadro**.
- **Tamanho do CSS:** hoje são 26,3 KB, para um limite de 30 KB (teste automático). Ver DEC-T5.
- **Segurança:** todo texto dinâmico continua passando por `e()`; CSRF e confirmações preservados.

## 6. Regras de negócio

Nenhuma afetada. Regras desta melhoria:

- RN-T01 Mesmos endpoints, métodos, nomes de campo, tokens CSRF, `id`s usados por scripts e âncoras
  (`#receitas`, `#notas`, `#dados`, `#l<id>`, `#doc-XX`) e confirmações (`confirm()`).
- RN-T02 Textos de situação e de mensagens **mantidos** (por exemplo, "PDF não gerado", "Desatualizado", "alterado",
  "não fechou", "não entram", "Nenhum lançamento ainda…").
- RN-T03 Nenhum valor novo é calculado: os indicadores usam os totais que as telas **já** recebem.

## 7. Fluxos de utilização

Os mesmos de hoje. Muda só a navegação de volta: pela trilha, em vez do link "← Voltar".

## 8. Exceções e erros

- EX-T01 Objeto muito longo: cortado com "…" na trilha e no subtítulo, completo no título da tela do Projeto.
- EX-T02 Projeto sem objeto (dado antigo): a trilha usa "Projeto #N".
- EX-T03 Erros de validação nos formulários: aparecem como hoje (mensagens fixas), e os valores digitados são mantidos.

## 9. Critérios de aceitação

- [x] CA-T01 Todas as telas internas têm trilha; não sobra nenhum "← Voltar". *(teste automático)*
- [x] CA-T02 As 4 telas que geram PDF mostram o cartão do PDF no mesmo formato, com os textos atuais.
- [x] CA-T03 A caixa amarela `aviso` só aparece em alertas reais (conciliação que não fechou); as listas vazias usam
      `estado-vazio`.
- [x] CA-T04 Nenhum `style="..."` nos templates de tela (os de PDF não entram). *(teste automático)*
- [x] CA-T05 Balancete e conciliação mostram os indicadores; o da conciliação diz "fechou" ou "não fechou" em texto.
- [ ] CA-T06 Com 360 px de largura não há rolagem horizontal da página; as listas de projetos e municípios aparecem
      como cartões.
- [ ] CA-T07 Todos os fluxos continuam funcionando: cadastros, lançamentos, metas, notas, receitas (inclusive
      "Restaurar"), devoluções e extrato, junção (enviar, arrastar, setas, remover, gerar) e geração e download dos PDFs.
- [x] CA-T08 Nenhum teste existente é alterado e nenhuma falha nova aparece (a referência é a lista de falhas de
      ambiente deste computador, registrada no `HISTORICO.md`).
- [ ] CA-T09 Conferência visual do usuário no navegador (desktop e celular).

## 10. Restrições técnicas

- **Testes acoplados ao HTML que devem continuar válidos (amostra):** "Projetos" na lista; `class="estado-erro"` e
  "Voltar ao início" (ver DEC-T4); `class="tag aviso-tag" title="Diferente do balancete"`; "não fechou" e "não entram";
  `draggable="true"` (3×); valores formatados ("497.152,49" etc.); `value="…"` dos formulários; "1º Repasse",
  "1ª Contrapartida"; escape de `<script>`.
- Arquivos previstos (detalhe no plano): os 13 templates de tela, `public/assets/app.css` (componentes novos:
  trilha com objeto, cartão do PDF, lista em cartões, rodapé de formulário) e, se for preciso, um pequeno helper PHP
  para a trilha (evitar repetir a mesma marcação em 10 telas).

## 11. Fora do escopo

- Regras de negócio, cálculos, banco, rotas, PDFs (`templates/pdf/`).
- A tela do Projeto (já feita) e o layout geral (topo e rodapé, da fundação).
- Informação nova (por exemplo, progresso de cada projeto na lista), filtros, busca e JavaScript novo.
- Trocar as tabelas numéricas por cartões no celular.

## 12. Decisões pendentes

- [x] DEC-T1 **Aprovada.** **Trilha com o objeto resumido** (e não "Projeto #N"). *Recomendo:* diz em que projeto você está.
      *Padrão: aprovar.*
- [x] DEC-T2 **Aprovada.** **Indicadores (KPIs) no balancete e na conciliação** (RF-T13 e RF-T17). Só destacam totais que já estão
      na tela. *Recomendo aprovar.* *Padrão: aprovar.*
- [x] DEC-T3 **Aprovada.** **"Cancelar" como botão secundário** nos formulários (RF-T05). *Recomendo aprovar.* *Padrão: aprovar.*
- [x] DEC-T4 **Decidido: manter "Voltar ao início".** **Página de erro: "Voltar ao início" → "Voltar aos projetos"** (RF-T20). Um teste procura "Voltar ao
      início", então aprovar isso exige atualizar esse teste (alteração mínima, registrada). *Recomendo:* **manter**
      "Voltar ao início" e não mexer no teste. *Padrão: manter.*
- [x] DEC-T5 **Aprovada.** **Limite de tamanho do CSS** (30 KB, teste da fundação). Os componentes novos podem passar disso.
      *Recomendo:* reaproveitar ao máximo e, **só se passar**, subir o limite para **40 KB** (o sistema é local, então
      não há custo de rede). *Padrão: subir para 40 KB se necessário.*

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação: levantamento das 13 telas restantes e proposta de padrão único, a partir do modelo da tela do Projeto | 2026-11-12 |
| 1.1 | Aprovada pelo usuário com as recomendações: DEC-T1, T2, T3 e T5 aprovadas; DEC-T4 = manter "Voltar ao início" | 2026-11-12 |
| 1.2 | **Implementada.** CA-T01 a T05 e T08 verificados (276 testes; nenhuma falha nova; nenhum teste antigo alterado). CSS ficou em 29,3 KB, então a DEC-T5 não foi necessária. CA-T06, T07 (fluxos no navegador) e T09 aguardam a conferência do usuário. Acréscimos de texto de apoio: subtítulos nas listas de projetos e municípios, "(copiados para cada projeto novo)" nos signatários do município e "(opcionais)" nos documentos do lançamento | 2026-11-12 |
