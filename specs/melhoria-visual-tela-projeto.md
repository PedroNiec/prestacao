
# SPEC — Melhoria visual: Tela do Projeto (Fase 2.1)

**Versão:** 1.3
**Status:** Aprovada (2026-11-12)
**Data:** 2026-11-12
**Relacionada a:** `specs/melhoria-visual-fundacao.md` (fundação visual, já implementada) e
`specs/prestacao-de-contas.md` (sistema). Esta spec **não altera funcionalidades nem regras de negócio**.

---

## 1. Contexto

A tela do Projeto (`GET /projetos/{id}`) é o centro do sistema: mostra os dados do projeto e os 25
documentos da prestação de contas, com as ações de cada um (enviar, substituir, ver, baixar, remover,
gerenciar arquivos, abrir formulário). Hoje ela já usa a fundação visual (cores, botões, etiquetas),
mas sua **estrutura** não foi repensada.

**Medição (projeto com dados, 2026-11-12):**

| Indicador | Valor |
|---|---|
| Elementos interativos na tela (`a`, `button`, `input`, `select`) | **74** |
| Campos de arquivo visíveis ao carregar | **13** (cada um com seu botão "Enviar/Substituir") |
| Formulários renderizados | 17 |
| Linhas "Vazio" entre as 25 | 16 (dominam o visual) |
| Coluna "Como é preenchido" | 5 valores distintos; "Upload de arquivo" repete 11× |
| Estilos inline | 0 |

**Problemas identificados**

1. **Excesso de controles:** os 13 campos de arquivo competem com o conteúdo; a pessoa vê "formulário"
   onde precisa ver "situação".
2. **Hierarquia fraca:** todas as linhas têm o mesmo peso; não há uma ação principal por linha.
3. **Título pouco informativo:** "Projeto #1" não diz qual projeto é; o objeto está enterrado na 5ª linha dos dados.
4. **Ação destrutiva exposta:** "Excluir" (vermelho sólido) fica colado a "Editar dados", sempre visível.
5. **Coluna redundante:** "Como é preenchido" ocupa largura e quase não ajuda a decidir.
6. **Dados do projeto:** PP/NE/NL numa linha só com barras e travessões ("— / — / —"); assinaturas numa frase.
7. **Texto de desenvolvimento na interface:** "O formulário da relação de bens será habilitado na próxima etapa" e "Disponível em etapa futura".
8. **Tela estreita:** a tabela de 5 colunas rola horizontalmente dentro do quadro.
9. **Âncoras × mensagens fixas:** ao voltar de uma ação (`#doc-18`), a linha alvo pode ficar sob a mensagem fixa.

## 2. Objetivo

Reorganizar a tela para que, ao abri-la, a pessoa **entenda de imediato qual é o projeto e o que falta**, com
**uma ação principal por documento** e os controles secundários recolhidos, em desktop e em tela estreita —
**sem alterar nenhum comportamento**.

## 3. Usuários envolvidos

Um único usuário (quem prepara a prestação de contas).

## 4. Requisitos funcionais (proposta visual)

- RF-P01 **Cabeçalho da página:** trilha de navegação `Projetos › Projeto #N`; título (h1) com o **objeto**
  do projeto; subtítulo com **município - UF · Projeto #N**; ação "Editar dados" (secundária). A tag
  `<title>` da aba continua "Projeto #N — Prestação de Contas".
- RF-P02 **Zona de perigo:** "Excluir projeto" sai do cabeçalho e vai para um cartão discreto no **fim da
  página**, com o aviso de que apaga todos os documentos. Mesmo formulário, mesma rota, **mesma confirmação**.
- RF-P03 **Cartão "Dados do projeto":** grade de duas colunas com Concedente, Processo, PP, NE, NL (cada um
  com seu rótulo), Local e data, Contador (nome e CRC) e Prefeito. Campos vazios aparecem como "—". O objeto
  fica no título (não se repete).
- RF-P04 ~~**Resumo dos documentos**~~ — **não será feito** (DEC-P1 = fora, v1.2): "N de 25 com arquivo · M desatualizados", derivado dos dados
  já carregados pela tela.
- RF-P05 **Lista de documentos** continua sendo uma `<table>` (dado tabular), agora com 4 colunas:
  `Nº | Documento | Situação | Ações`. O **tipo** ("Upload de arquivo", "Junção de PDFs", "Formulário no
  sistema", "Converte PowerPoint/PNG em PDF") passa a ser um texto pequeno abaixo do nome do documento.
- RF-P06 **Ação principal por linha**, de acordo com o estado (mesmos destinos de hoje):

  | Tipo | Sem arquivo | Com arquivo |
  |---|---|---|
  | Upload / conversão (11 + 2) | botão **Enviar arquivo ▾** | **Ver** · **Baixar** · **Substituir ▾** · *Remover* (discreto) |
  | Junção (8) | **Gerenciar arquivos** | **Gerenciar arquivos** · Baixar |
  | Formulário (4) | **Abrir …** (balancete, físico-financeiro, conciliação) | **Abrir …** · Baixar |

  "▾" = controle nativo `<details>`: ao acioná-lo, abre-se no próprio lugar o **mesmo formulário de envio de
  hoje** (campo de arquivo + botão). Funciona sem JavaScript.
- RF-P07 **Destaque de pendência:** linhas "Desatualizado" ganham marca lateral âmbar (além da etiqueta com
  texto, que continua a ser a fonte da informação).
- RF-P08 **Documento ainda não disponível** (09 — relação de bens): a linha **mantém** o texto atual
  "Disponível em etapa futura" (DEC-P3 = manter, v1.2).
- RF-P09 **Tela estreita (≤ 640 px):** cada documento vira um **cartão empilhado** (número e nome; situação;
  ações), sem rolagem horizontal; a semântica de tabela é preservada para leitores de tela.
- RF-P10 **Âncoras:** as linhas `#doc-XX` recebem margem de rolagem para não ficarem sob as mensagens fixas.
- RF-P11 **Texto de apoio:** "Nenhum documento é obrigatório." permanece sob o título da seção; a frase de
  desenvolvimento sobre a relação de bens também **permanece** (DEC-P3 = manter, v1.2).

### Esboço — desktop

```
Projetos › Projeto #1
┌──────────────────────────────────────────────────────────────────────────┐
│ AQUISIÇÃO DE UM VEÍCULO ÔNIBUS PARA O TRANSPORTE ESCOLAR   [Editar dados] │
│ Caxambu do Sul - SC · Projeto #1                                          │
└──────────────────────────────────────────────────────────────────────────┘
┌ Dados do projeto ────────────────────────────────────────────────────────┐
│ Concedente  450001 - Secretaria de Estado da Educação (SED)               │
│ Processo    PROCESSO SCC 7909/2025 - CONVÊNIO SIMPLIFICADO 2025CS1461     │
│ PP 2025PP052544   NE 2025NE009395   NL 2025NL116649                       │
│ Local/data  Caxambu do Sul - SC — 05/03/2026                              │
│ Contador    MAICON LUCAS LUFT (CRC/SC 102.316)   Prefeito  EDI MARCOS ... │
└──────────────────────────────────────────────────────────────────────────┘
Documentos da prestação de contas
Nenhum documento é obrigatório.
 Nº  Documento                          Situação            Ações
 01  Balancete                          [Gerado]            [Abrir balancete]  Baixar
     Formulário no sistema               6 lançamento(s)
 03  Notas fiscais                      [Desatualizado]     [Gerenciar arquivos]  Baixar
     Junção de PDFs                      4 arquivo(s)
 17  Cadastro Nacional de Obras (CNO)   [Vazio]             [Enviar arquivo ▾]
     Upload de arquivo
 18  Parecer do Controle Interno        [Enviado]           Ver  Baixar  [Substituir ▾]  Remover
     Upload de arquivo                   Parecer.pdf · 2 KB
 ...
┌ Zona de perigo ──────────────────────────────────────────────────────────┐
│ Excluir este projeto apaga todos os seus documentos.   [Excluir projeto]  │
└──────────────────────────────────────────────────────────────────────────┘
```

### Esboço — tela estreita (≈ 360 px)

```
Projetos › Projeto #1
AQUISIÇÃO DE UM VEÍCULO ÔNIBUS
PARA O TRANSPORTE ESCOLAR
Caxambu do Sul - SC · Projeto #1
[Editar dados]
Dados do projeto  (1 coluna)
┌──────────────────────────────┐
│ 18  Parecer do Controle Int. │
│ Upload de arquivo            │
│ Situação: [Enviado]          │
│ Parecer.pdf · 2 KB           │
│ Ver  Baixar  [Substituir ▾]  │
│ Remover                      │
└──────────────────────────────┘
```

## 5. Requisitos não funcionais

- **Sem comportamento novo:** nada de JavaScript novo (o recolhimento usa `<details>` nativo).
- **Acessibilidade:** `<details>/<summary>` operáveis por teclado; foco visível; rótulos de acessibilidade
  dos campos de arquivo preservados; informação nunca só por cor.
- **Desempenho:** a página não deve crescer de forma relevante (hoje ≈ 24 KB de HTML).
- **Compatibilidade:** navegadores atuais (DEC-V4).
- **Segurança:** todo texto dinâmico continua passando por `e()`; CSRF e confirmações preservados.

## 6. Regras de negócio

Nenhuma afetada. Regras desta melhoria:

- RN-P01 Mesmos endpoints, métodos, nomes de campo (`arquivo`), tokens CSRF, `enctype`, `accept`, `required`,
  `target="_blank"` do "Ver" e confirmações (`confirm()`) de hoje.
- RN-P02 A situação de cada documento (Vazio, Enviado, Convertido, Gerado, Não gerado, Desatualizado) e os
  textos dessas etiquetas continuam **idênticos**.
- RN-P03 A ordem e os códigos dos 25 documentos não mudam.

## 7. Fluxos de utilização

### Fluxo principal
1. Abrir um projeto: ver título (objeto) e dados.
2. Percorrer a lista de documentos: situação à vista, uma ação por linha.
3. Enviar um arquivo: acionar **Enviar arquivo ▾**, escolher o arquivo, **Enviar** → redirecionamento à própria
   linha com mensagem (comportamento atual).
4. Gerenciar junção / abrir formulário: botão da linha.
5. Excluir projeto (raro): descer até a "Zona de perigo" e confirmar.

### Fluxos alternativos
1. Substituir ou remover um arquivo já enviado (mesmas ações, recolhidas).
2. Baixar um documento já gerado.

## 8. Exceções e erros

- EX-P01 Projeto inexistente: continua 404 com a página de erro atual.
- EX-P02 Navegador sem suporte a `<details>`: o conteúdo permanece visível (degradação para o formulário aberto).
- EX-P03 Texto muito longo no objeto: o título quebra em várias linhas, sem estourar a página.

## 9. Critérios de aceitação

- [x] CA-P01 Ao carregar, **nenhum campo de arquivo está visível**; cada um aparece ao acionar "Enviar arquivo"
      ou "Substituir" da sua linha (e envia exatamente como antes). *(verificado por teste automático, 2026-11-12)*
- [x] CA-P02 Cada linha tem **no máximo uma ação destacada** (botão); as demais são links ou ações discretas. *(verificado por teste automático, 2026-11-12)*
- [x] CA-P03 O título mostra o objeto e o município; "Excluir projeto" fica só na zona de perigo e mantém a
      confirmação e o efeito. *(verificado por teste automático, 2026-11-12)*
- [ ] CA-P04 Com 360 px de largura **não há rolagem horizontal**, nem da página nem da lista de documentos.
- [x] CA-P05 As 25 linhas, seus códigos, ordem e situações permanecem; as etiquetas continuam com os mesmos textos
      e classes (`tag vazio`, `tag ok`, `tag aviso-tag`). *(verificado por teste automático, 2026-11-12)*
- [ ] CA-P06 Todos os fluxos existentes funcionam: enviar, substituir, remover, ver, baixar, gerenciar arquivos,
      abrir balancete/físico-financeiro/conciliação, editar dados, excluir projeto.
- [x] CA-P07 Os **247 testes** atuais passam (eram 244 quando a spec foi escrita); nenhum teste de comportamento é alterado (se um teste acoplado ao
      HTML precisar mudar, a mudança é mínima e registrada). *(nenhum teste existente alterado; 264 testes no total. Neste
      computador, 50 testes já falhavam **antes** da mudança por falta de Ghostscript/LibreOffice e por diferenças do
      Windows; nenhuma falha nova.)*
- [ ] CA-P08 Navegação por Tab percorre a lista de forma lógica, com foco visível, incluindo `<details>`.
- [ ] CA-P09 Ao voltar de uma ação (`#doc-XX`), a linha alvo fica visível abaixo da mensagem fixa.

## 10. Restrições técnicas

- Só HTML e CSS (sem JavaScript novo e sem dependências).
- **Testes acoplados ao HTML desta tela e que devem continuar válidos:** 25 ocorrências de `class="tag vazio"`
  (projeto sem documentos); exatamente 1 `class="tag ok"` após um envio; textos "Não gerado", "Desatualizado",
  "Convertido", "Abrir balancete", "Abrir físico-financeiro", "Abrir conciliação", "3 lançamento(s)",
  "1 linha(s)", "1 nota(s) fiscal(is)", `image/png,.png`, `.pptx`. **O resumo (RF-P04) não pode usar a classe
  `tag ok`** (ou o teste seria afetado).
- Arquivos previstos (detalhe no plano): `templates/projetos/show.php`, `public/assets/app.css`
  (seção própria da tela) e, se aprovado, um pequeno apresentador em PHP para calcular situação e ação de cada
  linha (hoje essa lógica está repetida dentro do template).

## 11. Fora do escopo

- Qualquer outra tela (lista de projetos, balancete etc.: fases seguintes).
- PDFs, regras de negócio, banco, rotas e textos de negócio (exceto os dois textos citados em DEC-P3).
- Filtro por situação, janela de confirmação própria e envio automático ao escolher arquivo (DEC-V1; ver DEC-P2).
- Reordenar, agrupar ou renumerar documentos.

## 12. Decisões pendentes

- [x] DEC-P1 **Decidido em 2026-11-12: fora.** **Resumo "N de 25 com arquivo · M desatualizados"** (RF-P04). Acrescenta uma informação nova, só de
      apresentação, calculada com dados já carregados. *Recomendo incluir:* é o que responde "falta muita coisa?"
      sem contar linhas. *Padrão se não houver resposta: fora.*
- [ ] DEC-P2 **Filtro por situação** (mostrar só pendentes/desatualizados). Exigiria JavaScript. *Recomendo não
      incluir agora*: avaliar depois de ver a nova lista. *Padrão: fora.*
- [x] DEC-P3 **Decidido em 2026-11-12: manter os textos atuais.** **Dois textos de interface:** "Disponível em etapa futura" → etiqueta "Em breve"; remover "O formulário
      da relação de bens será habilitado na próxima etapa". São textos provisórios de desenvolvimento.
      *Recomendo aprovar.* *Padrão: manter os textos atuais.*
- [x] DEC-P4 **Mover "Excluir" para a zona de perigo** no fim da página (RF-P02). *Recomendo aprovar.*
      *Padrão: aprovar (faz parte da proposta).*
- [x] DEC-P5 **Título com o objeto** (e "Projeto #N" como subtítulo). Em objetos muito longos o título ocupa várias
      linhas. *Recomendo aprovar.* *Padrão: aprovar.*

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação inicial: levantamento com medição e proposta visual da tela do Projeto | 2026-11-12 |
| 1.1 | Especificação aprovada pelo usuário. DEC-P1 e DEC-P3 não foram respondidas e seguem com o padrão (fora/manter textos); DEC-P2, P4 e P5 conforme o padrão | 2026-11-12 |
| 1.2 | DEC-P1 e DEC-P3 respondidas pelo usuário: **sem resumo** e **textos atuais mantidos**. RF-P04 cancelado; RF-P08/RF-P11 passam a manter os textos; esboços e CA-P07 (247 testes) atualizados | 2026-11-12 |
| 1.3 | **Implementada** (2026-11-12). CA-P01/02/03/05/07 verificados por teste; CA-P04/06/08/09 aguardam conferência no navegador do usuário. Detalhes de apresentação: dados em grade de 3 colunas (2 em telas médias, 1 em estreitas) com Concedente e Processo em linha inteira; a zona de perigo acrescenta "Não é possível desfazer."; o aviso "Nenhum documento é obrigatório..." passou de caixa amarela para texto de apoio (mesmo texto) | 2026-11-12 |
