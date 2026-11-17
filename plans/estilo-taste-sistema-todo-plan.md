# PLAN — Novo estilo visual em todo o sistema (cor, fonte, botões e microinterações)

**Versão:** 1.1
**Status:** Aprovado e implementação autorizada em 2026-11-17 — etapas 0 a 4 concluídas; aguardando conferência (checkpoint 1)
**SPEC relacionada:** `specs/estilo-taste-sistema-todo.md` (v1.1, aprovada em 2026-11-17)
**Data:** 2026-11-17

---

## 1. Resumo da implementação

Como todas as cores, raios e a fonte já são **tokens** em `public/assets/app.css`, a base global se faz **trocando os valores dos
tokens** (nomes mantidos): nenhum template muda. Depois se acrescentam três coisas ao `app.css`: o `@font-face` da fonte embutida,
os botões em pílula e as microinterações (com `prefers-reduced-motion`). Em seguida, cada tela é conferida e ajustada **uma por vez**,
com pausa para o usuário.

## 2. Análise da arquitetura atual

- **Tokens** em `:root` de `app.css`: `--cor-primaria` (`#1f4e79`), `--cor-primaria-hover`, `--cor-primaria-suave`, `--foco`, `--fundo`,
  `--texto`, `--texto-suave`, `--info-bg/fg`, `--raio-1` (8 px), `--raio-2` (12 px), **`--raio-pill` (999 px, já existe)**, `--fonte`.
- **Botões:** `.btn` (`app.css:124`) usa `--raio-1`; variantes `.secundario`, `.perigo`, `.pequeno`; `button.link` é discreto.
- **CSS por área:** `projetos.css`, `municipios.css`, `balancete.css`, `guia-visual.css` só usam tokens, com **uma exceção**:
  `municipios.css:32` tem o azul `#1f4e79` **codificado dentro de um SVG embutido** (`%231f4e79`), que não pode usar variável CSS.
- **Guia visual** (`templates/guia_visual.php`) escreve os códigos das cores em texto (`#1f4e79`, `#173d5f`, `#e8f0f7`…). Já estava
  desatualizado: mostra `--fundo #f4f6f8`, enquanto o CSS tem `#f6f7f9`.
- **PDFs** usam `templates/pdf/_estilo.php`, **independente** do `app.css`. Um teste (`testPdfTemplatesWereNotChangedByTheVisualRefactor`)
  garante que não mudam.
- **Testes** (`tests/DesignSystemTest.php`) leem os tokens e conferem **contraste** (27 pares), limites de tamanho (app.css ≤ 40 KB,
  área ≤ 16 KB), `var()` definidas, classes usadas e **`testNoExternalResources`, que proíbe `@font-face`** (vai mudar, ver 10).
- **Baseline medido hoje:** `vendor/bin/phpunit` → **337 testes, 1406 asserções, OK** (24 s).
- **Fonte:** não existe `public/assets/fonts/`. O servidor PHP embutido serve arquivos estáticos de `public/` sem configuração.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `public/assets/app.css` | Alterar | Tokens de cor e fonte (RF-E01/E03), `@font-face`, `.btn` em pílula (RF-E04), microinterações e movimento reduzido (RF-E05), lateral (RF-E06) |
| `public/assets/fonts/InstrumentSans-Variable.woff2` | **Criar (arquivo de terceiros)** | Fonte embutida (DEC-E2). Subconjunto latino, só o eixo de peso, para ficar pequeno |
| `public/assets/fonts/OFL.txt` | Criar | Texto da licença OFL exigido para redistribuir a fonte |
| `public/assets/municipios.css` | Alterar | Trocar `%231f4e79` por `%230d7480` no SVG; manter o experimento de hover já feito |
| `public/assets/projetos.css`, `balancete.css`, `guia-visual.css` | Alterar só se a conferência da tela achar cor ou forma fora dos tokens | Etapas por tela |
| `templates/guia_visual.php` | Alterar | Atualizar os códigos de cor exibidos (inclui corrigir o `--fundo` já divergente) |
| `tests/DesignSystemTest.php` | Alterar | Trocar a proibição de `@font-face` por "só `@font-face` local, sem URL externa"; novos testes (ver 10) |
| `docs/HISTORICO.md`, `specs/estilo-taste-sistema-todo.md` | Alterar | Registrar o andamento (CLAUDE.md, itens 4 e 6) |
| `templates/*.php` (telas) | **Só se necessário**, uma tela por vez | Ex.: classe faltando num estado vazio. Nada de regra de negócio |
| `templates/pdf/*`, `src/*` | **Não tocar** | RN-E01 e RN-E02 |

## 4. Banco de dados

**Nenhuma alteração.** Sem tabelas, colunas, índices ou migrações.

## 5. Serviços e regras de negócio

**Nenhuma alteração** em `src/` (controllers, services, domain). Mudança exclusivamente de apresentação (CSS, uma fonte e um template
de documentação visual).

## 6. Fluxo técnico

1. O `layout.php` já carrega `/assets/app.css` e o CSS da área. O `@font-face` no `app.css` aponta para `/assets/fonts/…woff2`.
2. O navegador baixa a fonte do próprio servidor local (`font-display: swap`: o texto aparece na hora com o fallback e troca).
3. Os tokens novos propagam para todas as telas pelo nome. O `.btn` ganha o raio de pílula.
4. As microinterações são regras `:hover`/`:active` com `transition`; um bloco `@media (prefers-reduced-motion: reduce)` zera transições
   e `transform`.

## 7. Segurança e permissões

- **Sem novas requisições externas em execução.** A fonte é servida pelo próprio sistema (continua local, sem login, em `127.0.0.1`).
- **Download único da fonte na implementação:** acesso ao Google Fonts / repositório `google/fonts` para obter o `.woff2` e a licença.
  É um acesso de **leitura de um arquivo público, sem enviar dados do projeto**. O arquivo é conferido (tipo, tamanho, abre como fonte)
  antes de entrar no projeto.
- Nenhuma entrada de usuário nova; nenhuma mudança em escape de HTML, CSRF ou uploads.
- Nenhum script ou arquivo da skill `design-taste-frontend` é copiado para o sistema.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Largura da fonte nova difere da atual e quebra layout (tabelas, colunas do balancete, chips) | Texto cortado ou rolagem lateral | Conferir cada tela em 1440 e 390; subir o tamanho/largura só onde preciso; etapa por tela com pausa |
| Primária cai de 8,66:1 para 5,49:1 | Menos folga de contraste | Continua ≥ 4,5:1; testes de contraste automáticos cobrem os 27 pares |
| Azul fixo esquecido fora dos tokens (SVG do estado vazio, guia visual) | Mistura de azul e verde-azulado | Teste novo que procura `1f4e79`, `173d5f`, `e8f0f7` em CSS e templates (CA-01) |
| Teste que proíbe `@font-face` falha | Suíte vermelha | Alteração **intencional e restrita**: permitir só `@font-face` local; manter proibição de `@import` e URLs externas |
| Fonte não carrega (arquivo ausente, caminho no Windows) | Cai no fallback | Pilha de fallback mantida; teste confere que o arquivo existe e que o caminho no CSS bate |
| Pílula em botões largos ou com texto longo (mobile) | Aspecto estranho | `.pequeno` e botões 100% já previstos; conferir nas telas |
| Movimento em hover incomoda | Desconforto | Desligado sob `prefers-reduced-motion`; deslocamento de só 2 px |
| Troca de cor muda a identidade (DEC-E4) | Decisão sua, já registrada | Reversão pelas linhas da tabela do RF-E01 |
| Retrabalho entre telas | Tempo | Base global primeiro; telas só recebem o que destoar |

## 9. Estratégia de implementação

Trabalho na branch `experimento/taste-skill-municipios`. **Sem commit** até o usuário pedir. Cada etapa de tela termina com **pausa**.

- [x] **Etapa 0 — Preparação:** confirmar branch e suíte verde (337); fazer cópia do `app.css` atual em `plans/anexos/` para reversão.
- [x] **Etapa 1 — Fonte:** baixar `.woff2` (latin, eixo de peso) e `OFL.txt`; validar o arquivo; criar `public/assets/fonts/`.
- [x] **Etapa 2 — Base global em `app.css`:** tokens de cor (RF-E01), `@font-face` e `--fonte` (RF-E03), `.btn`/`.btn.pequeno`/`button.link`
      em pílula (RF-E04), microinterações e `prefers-reduced-motion` (RF-E05), lateral (RF-E06).
- [x] **Etapa 3 — Resíduos:** SVG do `municipios.css`, `guia_visual.php`; busca por azuis restantes.
- [x] **Etapa 4 — Testes:** ajustar `testNoExternalResources`; novos testes (ver 10); rodar a suíte inteira.
- [x] **Checkpoint 1 (usuário liberou seguir em 2026-11-17):** o usuário confere o sistema no navegador (todas as telas já mudam pela base).
- [x] **Etapa 5 — Municípios** (lista e formulário) → pausa (aguardando conferência do usuário).
- [ ] **Etapa 6 — Lista de projetos** → pausa.
- [ ] **Etapa 7 — Tela do Projeto e formulário de projeto** → pausa.
- [ ] **Etapa 8 — Balancete** (tela e lançamento) → pausa.
- [ ] **Etapa 9 — Conciliação** (tela, nota fiscal, receita) → pausa.
- [ ] **Etapa 10 — Físico-financeiro** (tela e formulário) → pausa.
- [ ] **Etapa 11 — Relação de bens** (tela e formulário) → pausa.
- [ ] **Etapa 12 — Junção de documentos** → pausa.
- [ ] **Etapa 13 — Guia visual e tela de erro** → pausa.
- [ ] **Etapa 14 — Fechamento:** relatório de validação, `docs/HISTORICO.md`, spec com status "Implementada"; parar o servidor
      extra de teste (porta 8020) e perguntar o destino da pasta `experimento-taste/`, da skill instalada e do `skills-lock.json`.

## 10. Testes e validações

- [ ] **Regressão:** `vendor/bin/phpunit` inteiro (baseline 337 OK) após as etapas 4 e 14, e a cada tela quando houver mudança de template.
- [ ] **Contraste:** os 27 pares de `testTokenContrast` com os tokens novos (cobre RF-E02 / CA-05).
- [ ] **Alterar** `testNoExternalResources`: manter `@import` e URLs externas proibidos; aceitar `@font-face` **apenas** com `url("/assets/fonts/…")`.
- [ ] **Novos testes:** (a) o arquivo `.woff2` e o `OFL.txt` existem e o caminho do `@font-face` aponta para um arquivo real (CA-02);
      (b) nenhum `1f4e79`, `173d5f` ou `e8f0f7` em `public/assets/*.css` nem em `templates/` fora de `pdf/` (CA-01);
      (c) `.btn` usa `--raio-pill` (CA-03); (d) `transition`/`transform` de hover estão desligados no bloco `prefers-reduced-motion` (CA-04);
      (e) `--fonte` começa por "Instrument Sans" e mantém `system-ui` no fallback.
- [ ] **PDFs:** `testPdfTemplatesWereNotChangedByTheVisualRefactor` segue verde; amostra de um PDF gerado conferida em imagem pelo Ghostscript (CA-07).
- [ ] **Navegador (conferência do usuário):** cada tela em 1440 e 390; aba de Rede sem nenhuma requisição externa (CA-02); foco por Tab visível;
      com "reduzir movimento" ligado no sistema operacional, sem animações (CA-04). Como apoio, o Claude pode gerar capturas com o Chrome
      sem interface em um servidor extra com cópia dos dados (porta 8011, como já documentado), sem tocar nos dados reais.
- [ ] **Medição de largura:** em cada tela, procurar rolagem lateral e texto cortado em 360 e 1440 px (CA-09).

## 11. Critérios de conclusão

- Todos os critérios CA-01 a CA-09 da spec atendidos.
- Suíte PHPUnit verde (337 + os novos testes), sem novas falhas.
- As 9 etapas de tela conferidas e aprovadas pelo usuário, uma a uma.
- Nenhuma alteração em `src/`, banco ou PDFs.
- `docs/HISTORICO.md` e a spec atualizados; relatório final entregue.
- Nada commitado sem pedido do usuário.

## 12. Registro de execução (2026-11-17)

- Fonte: `.woff2` do subconjunto **latino** (30 092 bytes; a estimativa era 100 KB), validado (assinatura `wOF2`, tamanho declarado = real).
- `--texto-suave` ficou em `#586670` e não em `#5d6b72` (EX-03): este último dava 4,38:1 sobre o total de tabela.
- `municipios.css`: o deslocamento do hover do experimento foi movido para `@media (prefers-reduced-motion: no-preference)`.
- Testes: suíte em **346 OK** (baseline 337 + 5 novos + 4 pares de contraste). Os 5 testes novos foram validados com defeitos injetados.
- Conferência por captura (Chrome sem interface, só leitura): lista de Projetos em 1440 px com a fonte carregada e a cor nova.
- Não confirmado: o Chrome sem interface não concluiu a captura de `/municipios` (tempo esgotado); conferir no navegador.

### Etapa 5 — Municípios (2026-11-17)

- **Lista:** conferida no Chrome do usuário (extensão), em 1440 px: fonte, cor, botões em pílula e hover (sombra + elevação) corretos. **Sem alteração de CSS além do que já havia.**
- **Formulário:** grupos "Identificação" e "Signatários padrão" estavam como caixa dentro de caixa, com o título do segundo grupo quebrando sobre a borda em 390 px.
  Agora são seções com título e linha separadora; o aviso "(copiados para cada projeto novo)" vai para uma linha própria. Classe nova `form-mun`
  (template `municipios/form.php` + `municipios.css`), sem tocar no `.form` compartilhado, para não afetar as outras telas antes da vez delas.
- **Testes:** `testFormKeepsAllFieldsAndUsesFlatSections` (campos, 2 grupos, regra sem borda). Suíte: **347 OK**.
- **Não conferido:** o formulário em 390 px **depois** da mudança. O Chrome sem interface travou nas capturas (também em 1440 px, em todas as
  tentativas); o formulário em 390 px **antes** da mudança foi visto. Conferir no navegador, com a janela estreita.
- **Observação sobre o ambiente:** capturas com Chrome sem interface falham de forma intermitente aqui (já era limitação registrada no
  `docs/HISTORICO.md`). A extensão do Chrome do usuário funciona e serve de conferência.
