<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

/** Bens da relação de bens (doc. 09) e o título do documento, por projeto. */
final class BensRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function list(int $projetoId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM bens WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$projetoId]);
        return $st->fetchAll();
    }

    public function count(int $projetoId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM bens WHERE projeto_id = ?');
        $st->execute([$projetoId]);
        return (int) $st->fetchColumn();
    }

    public function find(int $projetoId, int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM bens WHERE id = ? AND projeto_id = ?');
        $st->execute([$id, $projetoId]);
        return $st->fetch() ?: null;
    }

    /** @param array{descricao:string,valor_centavos:int} $d */
    public function create(int $projetoId, array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO bens (projeto_id, ordem, descricao, valor_centavos)
             VALUES (:p, (SELECT COALESCE(MAX(ordem), 0) + 1 FROM bens WHERE projeto_id = :p2), :descricao, :valor)'
        );
        $st->execute(['p' => $projetoId, 'p2' => $projetoId, 'descricao' => $d['descricao'], 'valor' => $d['valor_centavos']]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array{descricao:string,valor_centavos:int} $d */
    public function update(int $projetoId, int $id, array $d): void
    {
        $this->pdo->prepare('UPDATE bens SET descricao = ?, valor_centavos = ? WHERE id = ? AND projeto_id = ?')
            ->execute([$d['descricao'], $d['valor_centavos'], $id, $projetoId]);
    }

    public function delete(int $projetoId, int $id): void
    {
        $this->pdo->prepare('DELETE FROM bens WHERE id = ? AND projeto_id = ?')->execute([$id, $projetoId]);
    }

    /** Troca o bem de posição com o vizinho (-1 = para cima). Retorna false se não houve mudança. */
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
        $st = $this->pdo->prepare('UPDATE bens SET ordem = ? WHERE id = ? AND projeto_id = ?');
        $this->pdo->beginTransaction();
        try {
            foreach ($ids as $pos => $bid) {
                $st->execute([$pos + 1, $bid, $projetoId]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return true;
    }

    /** Título salvo ('' quando não há, o que significa o padrão). */
    public function titulo(int $projetoId): string
    {
        $st = $this->pdo->prepare('SELECT titulo FROM bens_config WHERE projeto_id = ?');
        $st->execute([$projetoId]);
        return (string) ($st->fetchColumn() ?: '');
    }

    public function salvarTitulo(int $projetoId, string $titulo): void
    {
        $this->pdo->prepare(
            'INSERT INTO bens_config (projeto_id, titulo) VALUES (?, ?)
             ON CONFLICT(projeto_id) DO UPDATE SET titulo = excluded.titulo'
        )->execute([$projetoId, $titulo]);
    }
}
