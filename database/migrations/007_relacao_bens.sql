-- Relação de bens adquiridos/produzidos/construídos (doc. 09): uma linha por bem.
-- Tudo é digitado pelo usuário (specs/relacao-de-bens.md). Valores em centavos.
CREATE TABLE bens (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id     INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    ordem          INTEGER NOT NULL,
    descricao      TEXT NOT NULL,
    valor_centavos INTEGER NOT NULL CHECK (valor_centavos > 0)
);
CREATE INDEX idx_bens_projeto ON bens(projeto_id, ordem);

-- Título do PDF por projeto; vazio = "RELAÇÃO DE BENS ADQUIRIDOS".
CREATE TABLE bens_config (
    projeto_id INTEGER PRIMARY KEY REFERENCES projetos(id) ON DELETE CASCADE,
    titulo     TEXT NOT NULL DEFAULT ''
);
