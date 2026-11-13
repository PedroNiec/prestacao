<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ConciliacaoRepository
{
    public const NOTA_CAMPOS = ['nota_fiscal', 'bruto_centavos', 'ir_centavos', 'iss_centavos', 'inss_centavos', 'outros_centavos'];
    public const RECEITA_CAMPOS = ['tipo', 'descricao', 'valor_centavos'];
    public const CONFIG_CAMPOS = [
        'titulo', 'rotulo_extrato', 'extrato_descricao', 'data_saldo', 'saldo_cc', 'saldo_aplic',
        'dev_conc_rend', 'dev_conc_indevido', 'dev_conc_tarifas', 'dev_conv_rend', 'dev_conv_saldo',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    // ---- Dados avulsos ----

    public function config(int $projetoId): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM conciliacao WHERE projeto_id = ?');
        $st->execute([$projetoId]);
        return $st->fetch() ?: null;
    }

    public function saveConfig(int $projetoId, array $d): void
    {
        $cols = implode(', ', self::CONFIG_CAMPOS);
        $vals = ':' . implode(', :', self::CONFIG_CAMPOS);
        $upd = implode(', ', array_map(fn ($c) => "$c = excluded.$c", self::CONFIG_CAMPOS));
        $st = $this->pdo->prepare("INSERT INTO conciliacao (projeto_id, $cols) VALUES (:projeto_id, $vals) ON CONFLICT (projeto_id) DO UPDATE SET $upd");
        $params = ['projeto_id' => $projetoId];
        foreach (self::CONFIG_CAMPOS as $c) {
            $params[$c] = $d[$c];
        }
        $st->execute($params);
    }

    // ---- Receitas (digitadas na conciliação) ----

    /** @return array<int,array<string,mixed>> */
    public function receitas(int $projetoId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM conciliacao_receitas WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$projetoId]);
        return $st->fetchAll();
    }

    public function receita(int $projetoId, int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM conciliacao_receitas WHERE id = ? AND projeto_id = ?');
        $st->execute([$id, $projetoId]);
        return $st->fetch() ?: null;
    }

    public function createReceita(int $projetoId, array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO conciliacao_receitas (projeto_id, ordem, tipo, descricao, valor_centavos)
             VALUES (:projeto_id, (SELECT COALESCE(MAX(ordem), 0) + 1 FROM conciliacao_receitas WHERE projeto_id = :p2), :tipo, :descricao, :valor_centavos)'
        );
        $st->execute(['projeto_id' => $projetoId, 'p2' => $projetoId] + $this->bindReceita($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function updateReceita(int $projetoId, int $id, array $d): void
    {
        $st = $this->pdo->prepare('UPDATE conciliacao_receitas SET tipo = :tipo, descricao = :descricao, valor_centavos = :valor_centavos WHERE id = :id AND projeto_id = :projeto_id');
        $st->execute($this->bindReceita($d) + ['id' => $id, 'projeto_id' => $projetoId]);
    }

    public function deleteReceita(int $projetoId, int $id): void
    {
        $this->pdo->prepare('DELETE FROM conciliacao_receitas WHERE id = ? AND projeto_id = ?')->execute([$id, $projetoId]);
    }

    public function moveReceita(int $projetoId, int $id, int $direcao): bool
    {
        $ids = array_map(fn ($r) => (int) $r['id'], $this->receitas($projetoId));
        $i = array_search($id, $ids, true);
        $j = $i === false ? -1 : $i + ($direcao < 0 ? -1 : 1);
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return false;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        $st = $this->pdo->prepare('UPDATE conciliacao_receitas SET ordem = ? WHERE id = ? AND projeto_id = ?');
        $this->pdo->beginTransaction();
        try {
            foreach ($ids as $pos => $rid) {
                $st->execute([$pos + 1, $rid, $projetoId]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return true;
    }

    private function bindReceita(array $d): array
    {
        return ['tipo' => $d['tipo'], 'descricao' => $d['descricao'], 'valor_centavos' => $d['valor_centavos']];
    }

    // ---- Notas fiscais ----

    /** @return array<int,array<string,mixed>> */
    public function notas(int $projetoId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM conciliacao_notas WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$projetoId]);
        return $st->fetchAll();
    }

    public function countNotas(int $projetoId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM conciliacao_notas WHERE projeto_id = ?');
        $st->execute([$projetoId]);
        return (int) $st->fetchColumn();
    }

    public function nota(int $projetoId, int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM conciliacao_notas WHERE id = ? AND projeto_id = ?');
        $st->execute([$id, $projetoId]);
        return $st->fetch() ?: null;
    }

    public function createNota(int $projetoId, array $d): int
    {
        $cols = implode(', ', self::NOTA_CAMPOS);
        $vals = ':' . implode(', :', self::NOTA_CAMPOS);
        $st = $this->pdo->prepare(
            "INSERT INTO conciliacao_notas (projeto_id, ordem, $cols)
             VALUES (:projeto_id, (SELECT COALESCE(MAX(ordem), 0) + 1 FROM conciliacao_notas WHERE projeto_id = :p2), $vals)"
        );
        $st->execute(['projeto_id' => $projetoId, 'p2' => $projetoId] + $this->bindNota($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function updateNota(int $projetoId, int $id, array $d): void
    {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", self::NOTA_CAMPOS));
        $st = $this->pdo->prepare("UPDATE conciliacao_notas SET $set WHERE id = :id AND projeto_id = :projeto_id");
        $st->execute($this->bindNota($d) + ['id' => $id, 'projeto_id' => $projetoId]);
    }

    public function deleteNota(int $projetoId, int $id): void
    {
        $this->pdo->prepare('DELETE FROM conciliacao_notas WHERE id = ? AND projeto_id = ?')->execute([$id, $projetoId]);
    }

    public function moveNota(int $projetoId, int $id, int $direcao): bool
    {
        $ids = array_map(fn ($r) => (int) $r['id'], $this->notas($projetoId));
        $i = array_search($id, $ids, true);
        $j = $i === false ? -1 : $i + ($direcao < 0 ? -1 : 1);
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return false;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        $st = $this->pdo->prepare('UPDATE conciliacao_notas SET ordem = ? WHERE id = ? AND projeto_id = ?');
        $this->pdo->beginTransaction();
        try {
            foreach ($ids as $pos => $nid) {
                $st->execute([$pos + 1, $nid, $projetoId]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return true;
    }

    private function bindNota(array $d): array
    {
        $out = [];
        foreach (self::NOTA_CAMPOS as $c) {
            $out[$c] = $d[$c];
        }
        return $out;
    }
}
