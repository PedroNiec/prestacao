# Guia: retomar o projeto em outro computador e chamar o Claude de lá

**Atualizado em:** 2026-11-12 (segunda sessão) · Leia junto com `docs/HISTORICO.md` (o que já foi feito e decidido).

**Resumo em 6 passos**

1. **Clone o repositório** (`git clone https://github.com/PedroNiec/prestacao.git`), ou, sem git, copie a pasta
   do projeto inteira (inclusive a oculta `.claude`), **sem** `vendor/`.
2. Instale no outro computador: PHP 8.3 ou mais recente (com as extensões), Composer, **Ghostscript** e
   **LibreOffice**. No Windows, veja a seção 2.2: o Ghostscript **não** está no `winget`.
3. Rode `composer install` e `composer test` (esperado: **328 testes OK**, nenhum pulado).
4. Abra o Claude Code **dentro da pasta do projeto**.
5. Cole o **prompt de retomada** da seção 5.
6. Ao terminar o dia, peça ao Claude para **atualizar `docs/HISTORICO.md`**.

---

## 1. O que copiar (e o que NÃO copiar)

| Item | Copiar? | Observação |
|---|---|---|
| Código, `specs/`, `plans/`, `docs/`, `templates/`, `tests/`, `database/`, `public/`, `src/`, `bin/` | **Sim** | Tudo que é seu |
| **`.claude/`** (pasta oculta) | **Sim, obrigatório** | Contém a skill `/spec-driven`. Sem ela, o comando não existe no outro computador |
| `CLAUDE.md`, `composer.json`, `composer.lock`, `phpunit.xml`, `.gitignore`, `README.md` | **Sim** | |
| `vendor/` | **Não** | Recriado com `composer install` |
| `storage/` | **Só se for usar o sistema lá** | É onde ficam **seus dados** (banco e PDFs). Ver seção 3 |
| `.idea/`, `.phpunit.result.cache` | Não | Só do editor e dos testes |
| `planilha/` | Opcional | Planilha de referência. **Contém dados reais** de convênio. **Não está no git**: copie à parte, se precisar (o Claude a lê para reproduzir layouts dos PDFs) |

> **Cuidado com arquivos ocultos:** ao copiar com pen drive, nuvem ou "arrastar", confirme que o `.claude`
> foi junto. No terminal: `ls -A` mostra os ocultos.

---

## 2. Preparar o outro computador

O sistema funciona no **Linux** e no **Windows** (validado nos dois). Ele **encontra sozinho** o Ghostscript e o
LibreOffice nas pastas padrão; as variáveis `PRESTACAO_GS` e `PRESTACAO_SOFFICE` só são necessárias se você
instalar em outra pasta.

| Programa | Para quê | Sem ele |
|---|---|---|
| PHP 8.3+ com `pdo_sqlite`, `gd`, `zip`, `mbstring`, `fileinfo`, `dom` | o sistema | não roda |
| Composer | instalar Dompdf e PHPUnit | não instala as bibliotecas |
| **Ghostscript** | junção de PDFs **e** conferência de páginas das conversões | a **junção** (docs 02–07, 13, 14) e a **conversão de PNG** (doc 16) falham com mensagem de como instalar |
| **LibreOffice** | converter PowerPoint em PDF | só o doc 08 falha, com mensagem de como instalar |

### 2.1 Ubuntu/Debian

```bash
sudo apt install php8.3-cli php8.3-sqlite3 php8.3-gd php8.3-zip php8.3-mbstring php8.3-xml \
                 composer ghostscript libreoffice-impress
```

Confira: `php -v`, `php -m`, `composer --version`, `gs --version`, `soffice --version`.

### 2.2 Windows

1. **PHP 8.3+** e **Composer**. No `php.ini`, habilite `pdo_sqlite`, `gd`, `zip`, `mbstring` e `fileinfo`.
2. **Ghostscript:** baixe o instalador oficial **"Ghostscript AGPL Release" para Windows 64 bits** em
   <https://ghostscript.com/releases/gsdnld.html> e instale com as opções padrão. Ele **não está no catálogo do
   `winget`**: o ID `ArtifexSoftware.GhostScript` não existe.
3. **LibreOffice:**
   ```powershell
   winget install --id TheDocumentFoundation.LibreOffice -e
   ```
4. Confira se o sistema encontra os dois (dentro da pasta do projeto, depois do `composer install`):
   ```bash
   php -r 'require "vendor/autoload.php"; echo App\Services\Ferramentas::ghostscript(), PHP_EOL, App\Services\Ferramentas::libreOffice(), PHP_EOL;'
   ```
   O esperado é algo como `C:\Program Files\gs\gs10.08.0\bin\gswin64c.exe` e
   `C:\Program Files\LibreOffice\program\soffice.exe`. Se aparecer só `gs` ou `soffice`, a ferramenta não foi
   encontrada.

### 2.3 Subir e testar

```bash
cd /caminho/do/projeto
composer install
composer test          # esperado: OK (328 tests), cerca de 2 minutos
composer serve         # abre em http://127.0.0.1:8000
```

O banco é criado e **migrado sozinho** na primeira requisição. Isso inclui a migração 007 (relação de bens), que só
cria tabelas. Use `composer serve`, porque ele já configura o limite de upload de 50 MB; com `php -S` puro o limite é
2 MB. **Não exponha à internet nem à rede.**

**Porta 8000 ocupada?** (No computador Windows atual, ela é usada por outro projeto.) Rode com os mesmos limites em
outra porta:

```bash
php -d upload_max_filesize=50M -d post_max_size=60M -d max_file_uploads=100 -S 127.0.0.1:8010 -t public public/index.php
```

---

## 3. Seus dados NÃO viajam sozinhos — regra de ouro

Os dados (municípios, projetos, lançamentos e PDFs) ficam em `storage/` **no computador onde você usa o
sistema**. O código e os dados são separados:

- **Código** (desenvolvimento): pode ir e voltar entre os computadores.
- **Dados** (`storage/`): **use em um computador só.** O banco é um arquivo local e **não sincroniza**; se você
  editar nos dois, a cópia mais antiga sobrescreve a mais nova e **você perde trabalho**.

Para levar os dados: **pare o servidor** e copie a pasta `storage/` inteira. Isso também serve de **backup**.

No computador de desenvolvimento você pode simplesmente deixar o `storage/` vazio: os testes usam um banco
e uma pasta **temporários** e nunca encostam nos seus dados.

---

## 4. Instalar e abrir o Claude Code

1. Instale o **Claude Code** no outro computador seguindo as instruções oficiais de instalação da
   Anthropic e faça login com a **mesma conta**.
2. Entre na pasta do projeto e abra o Claude **de dentro dela** (é daí que ele lê o `CLAUDE.md` e as skills):

```bash
cd /caminho/do/projeto
claude
```

### O que o Claude de lá sabe e o que não sabe

| | |
|---|---|
| ✔ **Sabe** | `CLAUDE.md` (regras do projeto), a skill `/spec-driven`, todos os arquivos, as specs, os planos e este `docs/` |
| ✘ **Não sabe** | **As conversas anteriores** e a **memória** do Claude do outro computador (ela é local de cada máquina e não é copiada) |

Por isso, **todo o contexto que importa está nos documentos**. A primeira coisa a fazer é mandar o Claude
lê-los. A seção 2 do `HISTORICO.md` registra também **como você prefere conduzir o trabalho** (aprovações,
esboços visuais etc.).

> Aviso: `CLAUDE.md`, a skill e os dois modelos (`SPEC_TEMPLATE.md`, `PLAN_TEMPLATE.md`) estão em
> codificação **ISO-8859-1**; os demais arquivos são UTF-8. O Claude pode avisar que "não é UTF-8" ao tentar
> editá-los. É esperado e inofensivo. Alguns acentos podem aparecer estranhos no terminal.

---

## 5. Prompts prontos (copie e cole)

### 5.1 Retomada (use **sempre** na primeira mensagem)

```text
Leia CLAUDE.md, docs/HISTORICO.md e docs/GUIA-OUTRO-COMPUTADOR.md.
Rode `composer test` e me diga se está tudo verde.
Depois resuma: o que já está pronto, o que está pendente e qual é o próximo passo recomendado.
Não altere nenhum arquivo ainda e siga o processo SDD (CLAUDE.md): nada de implementar sem as 3 aprovações.
```

### 5.2 Retomar o redesenho visual (fase 3)

```text
/spec-driven Vamos retomar o redesenho visual tela por tela, no mesmo estilo aprovado de Municípios
(specs/redesenho-layout-municipios.md). O CSS já foi dividido por área (ver HISTORICO §3.3).
Próxima tela: [formulários / balancete / conciliação / físico-financeiro / junção] (a tela do Projeto já foi redesenhada).
Me mostre opções com esboços antes de escrever a spec.
```

### 5.3 Revisar as premissas provisórias

```text
/spec-driven Quero revisar as premissas provisórias do HISTORICO §6, começando pelas devoluções que ficam fora
do total de despesas da conciliação. Compare com a planilha (planilha/) e me pergunte uma decisão por vez.
```

### 5.4 Nova funcionalidade

```text
/spec-driven [descreva a ideia ou o problema com suas palavras]
```

### 5.5 Só conferir se tudo funciona no computador novo

```text
Não altere nada. Verifique se o ambiente está certo: versão do PHP e extensões, composer, e se o sistema
encontra o Ghostscript e o LibreOffice (App\Services\Ferramentas). Rode `composer test` e me diga o que faltar
instalar.
```

### 5.6 Para encerrar a sessão (use **sempre** no fim)

```text
Atualize docs/HISTORICO.md com o que fizemos hoje: o que foi concluído, as decisões tomadas, as pendências
e o próximo passo. Atualize também os status em specs/ e plans/ se algo mudou.
```

---

## 6. Como pedir as coisas (dicas)

- **Funcionalidade nova ou mudança relevante:** comece com `/spec-driven` e descreva a ideia.
- **Só quer entender algo:** diga **"não altere nenhum arquivo"**.
- **O Claude só avança com aprovação explícita** nos 3 pontos: spec, plano e autorização para implementar.
  Responda de forma clara ("aprovo com as recomendações", "aprovo e autorizo"). Se quiser mudar algo, diga o quê.
- **Telas:** o Claude **não consegue ver** a interface no ambiente dele. Abra o sistema no navegador e, ao
  fim de cada etapa visual, confira (Ctrl+F5 para pegar o CSS novo; F12 → modo dispositivo para ver em 360 px).
  Prints de tela ajudam muito.
- **PDFs:** com o Ghostscript instalado, o Claude consegue renderizar um PDF em imagem e olhar o resultado.
- **Se algo parecer diferente do que foi combinado**, aponte o documento (`specs/…`): ele é a referência.

---

## 7. Sincronizar o código entre os computadores (git)

O projeto usa **git**. O repositório é **público**: `https://github.com/PedroNiec/prestacao` (branch `main`).

**No outro computador (primeira vez):**

```bash
git clone https://github.com/PedroNiec/prestacao.git
cd prestacao
git config user.name  "Seu Nome"          # configure SÓ neste projeto, para não expor outro e-mail
git config user.email "seu-email-publico@exemplo.com"   # use o e-mail que você quer ver público nos commits
composer install && composer test
```

O clone já traz a pasta oculta `.claude` (a skill `/spec-driven`). **Não** vêm: `vendor/` (rode `composer install`),
`storage/` (seus dados) e `planilha/` (dados reais).

**Rotina de trabalho:**

```bash
git pull                                  # ANTES de começar: traz o que foi feito no outro computador
# ... trabalhar (com o Claude) ...
git add -A && git commit -m "descrição"   # ou peça ao Claude para commitar
git push                                  # DEPOIS: envia para o GitHub
```

- **Autenticação:** o GitHub não aceita senha no `git push` por HTTPS; use um *Personal Access Token*, o
  `gh auth login` (GitHub CLI) ou uma chave SSH.
- Trabalhe em **um computador por vez** e sempre faça `git pull` antes de começar, para evitar conflitos.
- **Windows:** com `core.autocrlf=true`, o git avisa "LF will be replaced by CRLF". É normal; os testes já
  normalizam as quebras de linha.
- **Nunca** coloque no git: `storage/`, `planilha/`, `.env` ou qualquer dado real. O `.gitignore` já exclui
  `vendor/`, `storage/*`, `planilha/`, `.idea/` e o cache do PHPUnit. **Como o repositório é público, tudo
  que for commitado fica visível para qualquer pessoa.**
- Sem git (pen drive, nuvem ou `rsync`): `rsync -av --exclude vendor --exclude storage --exclude .idea ./ usuario@outro-pc:/caminho/prestacao/`

---

## 8. Verificação rápida de saúde (5 minutos)

- [ ] `ls -A` mostra a pasta `.claude`.
- [ ] `php -m` lista `pdo_sqlite`, `gd`, `zip`, `mbstring`, `fileinfo`, `dom`.
- [ ] `composer install` terminou sem erro.
- [ ] O sistema encontra o Ghostscript e o LibreOffice (comando da seção 2.2, passo 4).
- [ ] `composer test` → **OK (328 tests)**, sem "skipped".
- [ ] `composer serve` (ou a porta alternativa) e o navegador abre a lista de projetos, com o **menu lateral**.
- [ ] `/guia-visual` abre o guia visual.
- [ ] No Claude: o comando `/spec-driven` aparece na lista de comandos.

---

## 9. Problemas comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| `could not find driver` | falta a extensão SQLite | Linux: `sudo apt install php8.3-sqlite3`; Windows: habilitar `pdo_sqlite` no `php.ini` |
| `Class "Dompdf\Dompdf" not found` | falta `composer install` | rodar `composer install` |
| Erro ao gerar PDF de imagem (PNG) | falta a extensão `gd` **ou** o Ghostscript | habilitar `gd`; instalar o Ghostscript |
| "O Ghostscript não está instalado…" | Ghostscript ausente ou fora da pasta padrão | Linux: `sudo apt install ghostscript`; Windows: instalador oficial (seção 2.2) ou `PRESTACAO_GS` |
| "O LibreOffice não está instalado…" | LibreOffice ausente (só doc. 08) | Linux: `sudo apt install libreoffice-impress`; Windows: `winget install --id TheDocumentFoundation.LibreOffice -e` |
| `winget`: "Nenhum pacote encontrou os critérios" para o Ghostscript | o Ghostscript não está no `winget` | instalador oficial (seção 2.2) |
| ~48 testes falham (junção, conversão, PDFs) | Ghostscript/LibreOffice ausentes | instalar e rodar de novo |
| Upload falha com arquivo grande | servidor iniciado com `php -S` puro | use `composer serve` ou o comando da seção 2.3 |
| Porta 8000 ocupada | outro programa na porta | comando da seção 2.3 (porta 8010) |
| Visual antigo depois de atualizar | cache do navegador | Ctrl+F5 |
| Acesso negado (403) | acessando por IP/nome diferente de localhost | abra por `127.0.0.1` ou `localhost` |
| Testes falham só neste computador | versão do PHP/extensões diferentes | rode o prompt 5.5 e envie a saída |
| `/spec-driven` não aparece | a pasta `.claude` não foi copiada | copie `.claude/skills/spec-driven/` |

---

## 10. Segurança e privacidade

- O sistema **não tem login**: use só em computador seu e **não** exponha à rede.
- `storage/` e `planilha/` têm **dados reais de convênios públicos**. Eles ficam **fora do git** de propósito:
  o repositório é **público**. Não os envie a repositórios, e-mails ou serviços de terceiros sem decidir
  conscientemente. Os testes e documentos usam alguns nomes e um CNPJ da planilha apenas como **exemplo**.
- Ao trocar de computador, apague os dados do computador que você deixar de usar, se for compartilhado.
