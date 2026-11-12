-- Conciliação bancária (doc. 15).

-- Blocos de despesa por nota fiscal (digitados). Líquido = bruto - IR - ISS - INSS - outros.
CREATE TABLE conciliacao_notas (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id     INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    ordem          INTEGER NOT NULL,
    nota_fiscal    TEXT NOT NULL DEFAULT '',
    bruto_centavos  INTEGER NOT NULL DEFAULT 0 CHECK (bruto_centavos >= 0),
    ir_centavos     INTEGER NOT NULL DEFAULT 0 CHECK (ir_centavos >= 0),
    iss_centavos    INTEGER NOT NULL DEFAULT 0 CHECK (iss_centavos >= 0),
    inss_centavos   INTEGER NOT NULL DEFAULT 0 CHECK (inss_centavos >= 0),
    outros_centavos INTEGER NOT NULL DEFAULT 0 CHECK (outros_centavos >= 0)
);
CREATE INDEX idx_conc_notas_projeto ON conciliacao_notas(projeto_id, ordem);

-- Dados avulsos da conciliação (um registro por projeto): textos, devoluções e extrato bancário.
CREATE TABLE conciliacao (
    projeto_id        INTEGER PRIMARY KEY REFERENCES projetos(id) ON DELETE CASCADE,
    titulo            TEXT NOT NULL DEFAULT '',
    rotulo_extrato    TEXT NOT NULL DEFAULT '',
    extrato_descricao TEXT NOT NULL DEFAULT '',
    data_saldo        TEXT NOT NULL DEFAULT '',
    saldo_cc          INTEGER NOT NULL DEFAULT 0 CHECK (saldo_cc >= 0),
    saldo_aplic       INTEGER NOT NULL DEFAULT 0 CHECK (saldo_aplic >= 0),
    dev_conc_rend     INTEGER NOT NULL DEFAULT 0 CHECK (dev_conc_rend >= 0),
    dev_conc_indevido INTEGER NOT NULL DEFAULT 0 CHECK (dev_conc_indevido >= 0),
    dev_conc_tarifas  INTEGER NOT NULL DEFAULT 0 CHECK (dev_conc_tarifas >= 0),
    dev_conv_rend     INTEGER NOT NULL DEFAULT 0 CHECK (dev_conv_rend >= 0),
    dev_conv_saldo    INTEGER NOT NULL DEFAULT 0 CHECK (dev_conv_saldo >= 0)
);

-- Valores calculados que o usuário sobrescreveu (RF-18). Apagar a linha restaura o calculado.
CREATE TABLE overrides (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id     INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    relatorio      TEXT NOT NULL,
    campo          TEXT NOT NULL,
    valor_centavos INTEGER NOT NULL CHECK (valor_centavos >= 0),
    UNIQUE (projeto_id, relatorio, campo)
);
