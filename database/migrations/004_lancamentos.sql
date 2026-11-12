-- Lançamentos do balancete (aba "Rel Pagamentos"): receita = entrou na conta; despesa = saiu.
CREATE TABLE lancamentos (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    projeto_id      INTEGER NOT NULL REFERENCES projetos(id) ON DELETE CASCADE,
    ordem           INTEGER NOT NULL,
    boletim_parcela TEXT NOT NULL DEFAULT '',
    descricao       TEXT NOT NULL,
    cnpj            TEXT NOT NULL DEFAULT '',
    nota_fiscal     TEXT NOT NULL DEFAULT '',
    nota_empenho    TEXT NOT NULL DEFAULT '',
    doc             TEXT NOT NULL DEFAULT '',
    data            TEXT NOT NULL DEFAULT '',
    origem          TEXT NOT NULL CHECK (origem IN ('convenio', 'rendimentos', 'contrapartida')),
    fonte           INTEGER NOT NULL CHECK (fonte IN (1, 2)),
    tipo            TEXT NOT NULL CHECK (tipo IN ('receita', 'despesa')),
    valor_centavos  INTEGER NOT NULL CHECK (valor_centavos > 0)
);
CREATE INDEX idx_lancamentos_projeto ON lancamentos(projeto_id, ordem);
