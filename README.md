# Prestação de Contas

Sistema **local** (uso por uma única pessoa) para montar a prestação de contas às prefeituras.
Especificação: `specs/prestacao-de-contas.md` · Planejamento: `plans/prestacao-de-contas-plan.md`.

## Pré-requisitos

- PHP 8.3 ou mais recente, com as extensões `pdo_sqlite`, `gd`, `zip`, `mbstring`, `fileinfo`
- Composer
- **Ghostscript**: junção de PDFs e conferência de páginas (inclusive na conversão de PNG)
- **LibreOffice**: só para converter o acervo fotográfico (PowerPoint) em PDF

### Linux

```bash
sudo apt install php8.3-sqlite3 php8.3-gd ghostscript libreoffice-impress
```

### Windows

- **Ghostscript:** baixe o instalador oficial "Ghostscript AGPL Release" para Windows 64 bits em
  <https://ghostscript.com/releases/gsdnld.html> e instale com as opções padrão (o Ghostscript não está no catálogo do
  `winget`).
- **LibreOffice:**

  ```powershell
  winget install --id TheDocumentFoundation.LibreOffice -e
  ```

O sistema encontra os dois sozinho nas pastas padrão (`C:\Program Files\gs\…\bin\gswin64c.exe` e
`C:\Program Files\LibreOffice\program\soffice.exe`). Se estiverem em outra pasta, defina as variáveis de ambiente
`PRESTACAO_GS` e `PRESTACAO_SOFFICE` com o caminho completo do executável. No `php.ini`, habilite as extensões
`pdo_sqlite`, `gd`, `zip`, `mbstring` e `fileinfo`.

## Instalação e execução

```bash
composer install
composer serve        # http://127.0.0.1:8000
```

O banco é criado e migrado automaticamente na primeira requisição. Para migrar manualmente:
`composer migrate`.

**Porta ocupada?** Se outro programa já usa a porta 8000, rode em outra porta:

```bash
php -d upload_max_filesize=50M -d post_max_size=60M -d max_file_uploads=100 -S 127.0.0.1:8010 -t public public/index.php
```

e abra `http://127.0.0.1:8010`.

## Segurança

O sistema **não tem login**. O servidor deve ouvir apenas em `127.0.0.1` (já configurado em
`composer serve`). **Não exponha à internet nem à rede local.**

## Dados e backup

Todos os dados ficam em `storage/` (`database.sqlite`, `uploads/`, `generated/`).
Para fazer backup, copie a pasta `storage/` com o servidor parado.

## Testes

```bash
composer test
```
