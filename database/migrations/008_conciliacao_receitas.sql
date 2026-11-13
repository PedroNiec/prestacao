-- Conciliação bancária: receitas digitadas na própria conciliação (independentes do balancete).
-- Substitui o cálculo a partir do balancete e as sobrescritas (tabela overrides, que só a conciliação usava).
CREATE TABLE conciliacao_receitas (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id     INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    ordem          INTEGER NOT NULL,
    tipo           TEXT NOT NULL CHECK (tipo IN ('repasse', 'contrapartida', 'rendimentos')),
    descricao      TEXT NOT NULL DEFAULT '',
    valor_centavos INTEGER NOT NULL CHECK (valor_centavos > 0)
);
CREATE INDEX idx_conc_receitas_projeto ON conciliacao_receitas(projeto_id, ordem);

DROP TABLE overrides;
