-- Arquivos de origem dos documentos de junção de PDFs (ordem definida pelo usuário).
CREATE TABLE arquivos (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id    INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    doc_codigo    TEXT NOT NULL,
    ordem         INTEGER NOT NULL,
    nome_original TEXT NOT NULL,
    caminho       TEXT NOT NULL,
    tamanho       INTEGER NOT NULL DEFAULT 0,
    criado_em     TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_arquivos_doc ON arquivos(projeto_id, doc_codigo, ordem);

-- 1 = a lista de arquivos mudou depois que o PDF final foi gerado.
ALTER TABLE documentos ADD COLUMN desatualizado INTEGER NOT NULL DEFAULT 0;
