-- Relatório de execução físico-financeiro (doc. 22): uma linha por meta/etapa.
-- Tudo é digitado pelo usuário. Quantidades em centésimos; valores em centavos.
CREATE TABLE fisico_financeiro (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id        INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    ordem             INTEGER NOT NULL,
    meta              TEXT NOT NULL DEFAULT '',
    etapa             TEXT NOT NULL DEFAULT '',
    descricao         TEXT NOT NULL,
    unidade           TEXT NOT NULL DEFAULT '',
    fis_prog_periodo  INTEGER NOT NULL DEFAULT 0 CHECK (fis_prog_periodo >= 0),
    fis_exec_periodo  INTEGER NOT NULL DEFAULT 0 CHECK (fis_exec_periodo >= 0),
    fis_prog_ate      INTEGER NOT NULL DEFAULT 0 CHECK (fis_prog_ate >= 0),
    fis_exec_ate      INTEGER NOT NULL DEFAULT 0 CHECK (fis_exec_ate >= 0),
    fin_conc_periodo  INTEGER NOT NULL DEFAULT 0 CHECK (fin_conc_periodo >= 0),
    fin_exec_periodo  INTEGER NOT NULL DEFAULT 0 CHECK (fin_exec_periodo >= 0),
    fin_rend_periodo  INTEGER NOT NULL DEFAULT 0 CHECK (fin_rend_periodo >= 0),
    fin_conc_ate      INTEGER NOT NULL DEFAULT 0 CHECK (fin_conc_ate >= 0),
    fin_exec_ate      INTEGER NOT NULL DEFAULT 0 CHECK (fin_exec_ate >= 0)
);
CREATE INDEX idx_fisfin_projeto ON fisico_financeiro(projeto_id, ordem);
