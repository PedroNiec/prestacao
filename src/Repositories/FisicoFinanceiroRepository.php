<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class FisicoFinanceiroRepository
{
    private const CAMPOS = [
        'meta', 'etapa', 'descricao', 'unidade',
        'fis_prog_periodo', 'fis_exec_periodo', 'fis_prog_ate', 'fis_exec_ate',
        'fin_conc_periodo', 'fin_exec_periodo', 'fin_rend_periodo', 'fin_conc_ate', 'fin_exec_ate',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function list(int $projetoId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM fisico_financeiro WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$projetoId]);
        return $st->fetchAll();
    }

    public function count(int $projetoId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM fisico_financeiro WHERE projeto_id = ?');
        $st->execute([$projetoId]);
        return (int) $st->fetchColumn();
    }

    public function find(int $projetoId, int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM fisico_financeiro WHERE id = ? AND projeto_id = ?');
        $st->execute([$id, $projetoId]);
        return $st->fetch() ?: null;
    }

    public function create(int $projetoId, array $d): int
    {
        $cols = implode(', ', self::CAMPOS);
        $vals = ':' . implode(', :', self::CAMPOS);
        $st = $this->pdo->prepare(
            "INSERT INTO fisico_financeiro (projeto_id, ordem, $cols)
             VALUES (:projeto_id, (SELECT COALESCE(MAX(ordem), 0) + 1 FROM fisico_financeiro WHERE projeto_id = :p2), $vals)"
        );
        $st->execute(['projeto_id' => $projetoId, 'p2' => $projetoId] + $this->bind($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $projetoId, int $id, array $d): void
    {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", self::CAMPOS));
        $st = $this->pdo->prepare("UPDATE fisico_financeiro SET $set WHERE id = :id AND projeto_id = :projeto_id");
        $st->execute($this->bind($d) + ['id' => $id, 'projeto_id' => $projetoId]);
    }

    public function delete(int $projetoId, int $id): void
    {
        $this->pdo->prepare('DELETE FROM fisico_financeiro WHERE id = ? AND projeto_id = ?')->execute([$id, $projetoId]);
    }

    public function move(int $projetoId, int $id, int $direcao): bool
    {
        $ids = array_map(fn ($r) => (int) $r['id'], $this->list($projetoId));
        $i = array_search($id, $ids, true);
        if ($i === false) {
            return false;
        }
        $j = $i + ($direcao < 0 ? -1 : 1);
        if ($j < 0 || $j >= count($ids)) {
            return false;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        $st = $this->pdo->prepare('UPDATE fisico_financeiro SET ordem = ? WHERE id = ? AND projeto_id = ?');
        $this->pdo->beginTransaction();
        try {
            foreach ($ids as $pos => $lid) {
                $st->execute([$pos + 1, $lid, $projetoId]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return true;
    }

    private function bind(array $d): array
    {
        $out = [];
        foreach (self::CAMPOS as $c) {
            $out[$c] = $d[$c];
        }
        return $out;
    }
}
