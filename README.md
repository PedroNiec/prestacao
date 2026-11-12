# Prestação de Contas

Sistema **local** (uso por uma única pessoa) para montar a prestação de contas às prefeituras.
Especificação: `specs/prestacao-de-contas.md` · Planejamento: `plans/prestacao-de-contas-plan.md`.

## Pré-requisitos

- PHP 8.3 com as extensões `pdo_sqlite`, `gd`, `zip`, `mbstring`, `fileinfo`
  (`sudo apt install php8.3-sqlite3 php8.3-gd`)
- Composer
- Ghostscript (`gs`) — junção de PDFs
- LibreOffice Impress (`sudo apt install libreoffice-impress`) — somente para converter o acervo
  fotográfico (PowerPoint) em PDF

## Instalação e execução

```bash
composer install
composer serve        # http://127.0.0.1:8000
```

O banco é criado e migrado automaticamente na primeira requisição. Para migrar manualmente:
`composer migrate`.

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
