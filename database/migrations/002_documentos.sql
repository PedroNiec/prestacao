-- PDF final de cada documento do projeto (um por documento).
-- Para documentos de upload é o próprio arquivo enviado; para junção/relatórios/conversões
-- é o PDF gerado. 'caminho' é relativo a storage/.
CREATE TABLE documentos (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id    INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    doc_codigo    TEXT NOT NULL,
    caminho       TEXT NOT NULL,
    nome_original TEXT NOT NULL DEFAULT '',
    tamanho       INTEGER NOT NULL DEFAULT 0,
    atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (projeto_id, doc_codigo)
);
