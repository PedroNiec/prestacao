# PLAN — Redesenho visual: Tela do Projeto (fase 3.3)

**Versão:** 1.0
**Status:** Implementado (2026-11-13)
**SPEC relacionada:** `specs/redesenho-tela-projeto.md` (v1.0, aprovada em 2026-11-13, com DEC-T1 a DEC-T5 recomendadas)
**Data:** 2026-11-13

---

## 1. Resumo da implementação

Reescreve a marcação de `templates/projetos/show.php` (cabeçalho de identidade, faixa de dados, **lista** de documentos em linhas largas com ação principal e menu ⋮)
e o CSS exclusivo dela em `public/assets/projetos.css`. O `DocumentoLinhaPresenter`, as rotas, os controllers e o banco **não mudam**.
As regras antigas da tabela saem da base. Os testes de `TelaProjetoTest` que dependem de `<tr>`/`role`/colunas são reescritos para a lista.
Como é uma mudança visual, há **pausa para conferência do usuário no navegador** antes de documentar.

## 2. Análise da arquitetura atual

- `show.php` monta cabeçalho, aviso do ZIP (`L::resumoZip`), cartão de dados (`dl.dados-grade`), tabela `.docs` com 25 `<tr id="doc-…">` e a zona de perigo.
- O presenter já entrega por linha: `codigo`, `nome`, `tipo`, `situacao{texto,classe}`, `detalhe`, `desatualizado`, `acao` (upload / junção / formulário / indisponível), `temArquivo`, `urls` (ver, baixar, enviar, remover, base), `formulario{url,rotulo}` e `envio{accept,rotuloCampo,dicaConversao}`. **Isso já basta** para a nova marcação.
- Painéis recolhidos usam `<details class="recolhe">` (também usado no formulário do balancete: `.recolhe` e `.recolhe-painel` ficam na **base**).
- O menu ⋮ já existe (`details.menu-mais` + `.menu-mais-painel`, na base) e abre para o lado/para cima conforme a linha.
- Uso das classes da seção 8 do `app.css` (levantado em 2026-11-13):
  - **compartilhadas (ficam na base):** `.projeto-cab`, `.projeto-titulo`, `.projeto-sub` (cabeçalho de 8 telas), `.cartao-titulo` (também `partes/pdf`), `.arq`, `.recolhe`, `.recolhe-painel`, `.envio`, `.rolagem`;
  - **exclusivas da tela do Projeto (vão para `projetos.css` ou saem):** `.projeto-dados`, `.dados-grade`, `.dado-largo`, `.docs*`, `.doc-linha`, `.doc-nome`, `.doc-tipo`, `.doc-pendente`, `.breve`, `.zona-perigo`.
- Em `projetos.css` já existem `.icone-pasta` (e a máscara do ícone via token `--icone-pasta` da base), reaproveitados no cabeçalho (DEC-T1).
- Testes que dependem da marcação atual: `tests/TelaProjetoTest.php` (maior parte); `BalanceteNaTelaTest` e `RelacaoBensTest` só citam `.recolhe`/`doc-` do próprio formulário/linha e não mudam.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `templates/projetos/show.php` | Alterar | Nova marcação (cabeçalho, faixa de dados, lista de documentos, ⋮, painéis); mesmas rotas, formulários e textos |
| `public/assets/projetos.css` | Alterar | Classes novas exclusivas da tela (`.doc-*`, faixa de dados, zona de perigo, estreito); limite 16 KB |
| `public/assets/app.css` | Alterar | Remove da seção 8 as regras antigas sem uso (`.docs*`, `.doc-linha`, `.dados-grade`, `.zona-perigo`…) |
| `tests/TelaProjetoTest.php` | Alterar | Reescreve os testes dependentes de `<tr>`/`role`/colunas; adiciona os da nova estrutura |
| `tests/DesignSystemTest.php` | Alterar (se preciso) | Só se algum teste citar classes removidas |
| `src/Presenters/DocumentoLinhaPresenter.php` | **Não altera** | Dados por linha já suficientes |
| `docs/HISTORICO.md`, `specs/redesenho-tela-projeto.md`, este plano | Alterar | Documentação e status sincronizados |

Nenhum controller, serviço, repositório, rota ou migração muda.

## 4. Banco de dados

Sem alterações.

## 5. Serviços e regras de negócio

Sem regra nova. Decisões de apresentação (por linha, a partir do que o presenter já informa):

| Tipo de linha | Ação principal (destaque, no máximo 1) | No menu ⋮ |
|---|---|---|
| Upload **sem** arquivo | **Enviar arquivo** (abre o painel de envio, `details.recolhe`) | — (sem ⋮) |
| Upload **com** arquivo | **Ver** | Baixar · Substituir (painel de envio dentro do menu) · Remover (confirmação atual) |
| Formulário no sistema | botão do formulário (ex.: Abrir balancete) | Baixar (se há PDF) |
| Junção | **Gerenciar arquivos** | Baixar (se há PDF) |
| Indisponível | nenhuma | — |

- **Painel de envio:** continua um `<details>` nativo (sem JavaScript). Para "Enviar arquivo" (sem arquivo) o painel abre **sob o botão como popover** (largura mínima ~320 px) em tela larga e **no fluxo** em tela estreita; para "Substituir" o painel fica **dentro do menu ⋮**, no fluxo (DEC-T2: mesmo painel/contrato de formulário). O contrato do formulário (rota, `enctype`, `accept`, `name="arquivo"`, `aria-label`, CSRF, dica de conversão) é idêntico ao atual.
- **Menu ⋮ só quando houver secundária** (EX-02): a marcação do menu é omitida nas linhas sem Baixar/Substituir/Remover.
- **Desatualizado** (DEC-T3): etiqueta `Desatualizado` (já vem do presenter) + classe `doc-pendente` na linha (borda esquerda e fundo de aviso, com os tokens existentes).
- **Âncora:** `id="doc-<código>"` no `<li>`, com `scroll-margin-top` para não ficar sob as mensagens fixas.

## 6. Fluxo técnico

`GET /projetos/{id}` → `ProjetoController::mostrar` (inalterado) → `show.php` percorre `$linhas` e escolhe, por `acao`/`temArquivo`, a ação principal e as secundárias da tabela acima → o layout carrega `app.css` + `projetos.css` (convenção de área) → o usuário aciona as mesmas rotas de hoje (enviar, remover, baixar, ver, gerenciar, abrir formulário, ZIP, excluir).

Estrutura da linha (esboço):

```html
<ul class="doc-lista">
  <li id="doc-02" class="doc-item [doc-pendente]">
    <span class="doc-num">02</span>
    <div class="doc-corpo"><span class="doc-nome">…</span><small class="doc-tipo">Junção de PDFs</small></div>
    <div class="doc-situacao"><span class="tag …">Vazio</span><small class="arq">detalhe</small></div>
    <div class="doc-acoes"> ação principal  +  <details class="menu-mais">…</details> </div>
  </li>
</ul>
```

## 7. Segurança e permissões

- Mesmos formulários POST com CSRF; texto dinâmico com `e()`; downloads por id (sem caminho).
- A confirmação de remoção ("Remover este arquivo?") e a do ZIP com pendências são preservadas (`onsubmit`/`onclick` iguais).
- Exclusão do projeto continua só na zona de perigo (1 ocorrência da rota), com a confirmação atual.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Painel de envio (popover) cortado pelo contêiner ou fora da tela | Não dá para enviar arquivo | Contêiner da lista sem `overflow` oculto; painel com `z-index` e posicionamento verificados no navegador, nas linhas do início e do fim e em tela estreita |
| `Substituir` dentro do menu ⋮ (details aninhado) confuso | Fluxo de substituição quebrado | Testar no navegador (abrir ⋮ → Substituir → painel → enviar); manter o texto "Substituir" no `summary` (contrato dos testes) |
| Regressão em outras telas por mover/remover regras da base | Aparência alterada em outras telas | Comparar o **estilo computado** (hash) das **outras 40 capturas** (21 telas × 2 larguras, menos a tela do Projeto), antes × depois: devem ser idênticas |
| Classe usada por outra área ficar só em `projetos.css` | Tela sem estilo | Testes de `css-por-area` (classe exclusiva só usada pela própria área) |
| Perda de acessibilidade ao trocar tabela por lista | Leitor de tela perde contexto | `ul`/`li` com nomes acessíveis; ⋮ com `aria-label` por documento; situação em texto (não só cor) |
| Testes antigos presos à marcação | Falhas em cascata | Reescrever `TelaProjetoTest` preservando as garantias (spec §10) |
| Limite de CSS | Estouro | `projetos.css` ~2,5 KB hoje, previsão ~6 KB (limite 16 KB); a base **diminui** |

## 9. Estratégia de implementação

- [x] **Etapa 0 — linha de base e ambiente de demonstração:** (a) registrar de novo o hash de estilo computado das 42 capturas e capturas de tela da tela do Projeto (antes) em 1440 px e 390 px;
      (b) subir um servidor **separado** (porta 8011) com **cópia** do banco e do storage (`PRESTACAO_DB`/`PRESTACAO_STORAGE`), com documentos em todos os estados
      (vazio, enviado, convertido, gerado, desatualizado, não gerado) para conferir a tela sem tocar nos seus dados reais.
- [x] **Etapa 1 — marcação e CSS:** nova `show.php` e novas regras em `projetos.css` (cabeçalho, faixa de dados, lista, estados, estreito); **sem** remover ainda as regras antigas da base.
- [x] **Etapa 2 — limpeza:** remover da seção 8 do `app.css` o que ficou sem uso; conferir nenhuma classe órfã (testes de design).
- [x] **Etapa 3 — testes:** reescrever `TelaProjetoTest`; manter/ajustar os demais; suíte completa.
- [x] **Etapa 4 — validação técnica:** hash de estilo computado das outras telas (iguais); capturas da nova tela (1440 e 390 px) nos vários estados; interação real no navegador (⋮, Enviar arquivo, Substituir, Ver/Baixar, Gerenciar, abrir formulário, âncoras, ZIP), contraste e foco.
- [x] **Etapa 5 — PAUSA para o usuário:** você confere a tela no navegador (CA-T10); ajustes finos (espaçamento, textos, posição do painel) até aprovar.
- [x] **Etapa 6 — documentação:** `HISTORICO.md`, status da spec e do plano, relatório final.

Pontos de pausa: ao fim da Etapa 4 (relatório + capturas) e a Etapa 5 (aprovação visual). Commits só quando você pedir.

## 10. Testes e validações

- [x] **Estrutura:** 25 itens `doc-<código>` na ordem do catálogo; `<h1>` com o objeto escapado; subtítulo "município - UF · Projeto #N"; `<title>` e trilha preservados; `dt/dd` dos dados (ex.: `<dt>PP</dt><dd>…</dd>`, "—" quando vazio, contador com CRC).
- [x] **Ações:** no máximo uma ação em destaque por linha; Ver/Baixar/Substituir/Remover conforme o tipo e o estado; ⋮ ausente quando não há secundárias; links de formulário (ex.: `…/relacao-de-bens`) e textos preservados; sem "Disponível em etapa futura".
- [x] **Upload:** 13 campos de arquivo no HTML, **nenhum fora de painel recolhido**; contrato do formulário (rota, `enctype`, `accept`, `aria-label`, dica de conversão do doc. 16).
- [x] **Segurança:** todo POST com `_csrf`; exclusão do projeto só na zona de perigo e com confirmação; confirmação de remoção; HTML escapado.
- [x] **Desatualizado:** `doc-pendente` + etiqueta só na linha desatualizada.
- [x] **CSS (design system):** classes usadas × definidas por área; classe exclusiva só usada por `projetos/`; tamanhos; contraste dos tokens usados no destaque.
- [x] **Regressão visual:** hash do estilo computado das outras telas idêntico; nova tela conferida em capturas e no navegador, em 1440 px e 390 px.
- [x] **Regressão geral:** suíte completa (hoje 333) passando.

## 11. Critérios de conclusão

CA-T01 a CA-T10 da spec atendidos (incluindo a aprovação visual do usuário); suíte completa passando; hashes das outras telas idênticos; base do CSS menor que hoje;
documentação sincronizada; servidor de demonstração (porta 8011) encerrado e arquivos temporários fora do projeto.

## 12. Decisões técnicas pendentes

- [x] DT-1: Painel "Enviar arquivo" como **popover** sob o botão (largo) e **no fluxo** em tela estreita; "Substituir" dentro do ⋮, no fluxo. Recomendado. Alternativa: sempre no fluxo, expandindo a linha.
- [x] DT-2: Validar com **servidor de demonstração (porta 8011) e cópia dos dados**, sem tocar no `storage/` real. Recomendado.
- [x] DT-3: Reutilizar `.icone-pasta` (de `projetos.css`) no cabeçalho em vez de criar outro ícone. Recomendado.

## 13. Aprovação

- [x] Especificação aprovada (2026-11-13).
- [x] Planejamento aprovado.
- [x] Autorização para implementação.

## 14. Relatório de execução (2026-11-13)

- Suíte completa: 337 testes passando. Estilo computado das **outras telas**: 40 de 42 capturas idênticas (as 2 diferentes são a própria tela do Projeto, nas duas larguras).
- A comparação revelou a remoção indevida da regra compartilhada `.cartao-titulo` (usada por `partes/pdf` em 5 telas); restaurada, e o conjunto de regras antes × depois foi conferido por script (só saíram as previstas).
- Acréscimos fora do plano, de baixo risco: `app.js` fecha menus ⋮ e painéis de envio (abrir outro, clicar fora, Esc); coluna de ações com largura fixa; destaque do desatualizado sem fundo amarelo.
- `.breve` e `.doc-corpo` ficaram em `projetos.css`; a base caiu para 27.669 bytes e `projetos.css` está em 6.984 bytes.
