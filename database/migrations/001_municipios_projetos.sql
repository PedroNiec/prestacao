CREATE TABLE municipios (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    nome          TEXT NOT NULL,
    uf            TEXT NOT NULL,
    prefeito_nome TEXT NOT NULL DEFAULT '',
    contador_nome TEXT NOT NULL DEFAULT '',
    contador_crc  TEXT NOT NULL DEFAULT '',
    criado_em     TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE projetos (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    municipio_id    INTEGER NOT NULL REFERENCES municipios(id) ON DELETE RESTRICT,
    concedente      TEXT NOT NULL DEFAULT '',
    processo        TEXT NOT NULL DEFAULT '',
    nr_pp           TEXT NOT NULL DEFAULT '',
    nr_ne           TEXT NOT NULL DEFAULT '',
    nr_nl           TEXT NOT NULL DEFAULT '',
    objeto          TEXT NOT NULL DEFAULT '',
    local           TEXT NOT NULL DEFAULT '',
    data_assinatura TEXT NOT NULL DEFAULT '',
    prefeito_nome   TEXT NOT NULL DEFAULT '',
    contador_nome   TEXT NOT NULL DEFAULT '',
    contador_crc    TEXT NOT NULL DEFAULT '',
    criado_em       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_projetos_municipio ON projetos(municipio_id);
