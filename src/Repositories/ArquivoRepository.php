<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ArquivoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function list(int $projetoId, string $codigo): array
    {
        $st = $this->pdo->prepare('SELECT * FROM arquivos WHERE projeto_id = ? AND doc_codigo = ? ORDER BY ordem, id');
        $st->execute([$projetoId, $codigo]);
        return $st->fetchAll();
    }

    public function find(int $projetoId, string $codigo, int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM arquivos WHERE id = ? AND projeto_id = ? AND doc_codigo = ?');
        $st->execute([$id, $projetoId, $codigo]);
        return $st->fetch() ?: null;
    }

    /** @return array<string,int> quantidade de arquivos por código de documento */
    public function countsByProjeto(int $projetoId): array
    {
        $st = $this->pdo->prepare('SELECT doc_codigo, COUNT(*) FROM arquivos WHERE projeto_id = ? GROUP BY doc_codigo');
        $st->execute([$projetoId]);
        return $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function add(int $projetoId, string $codigo, string $caminho, string $nome, int $tamanho): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO arquivos (projeto_id, doc_codigo, ordem, nome_original, caminho, tamanho)
             VALUES (:p, :c, (SELECT COALESCE(MAX(ordem), 0) + 1 FROM arquivos WHERE projeto_id = :p2 AND doc_codigo = :c2),
                     :nome, :caminho, :tam)'
        );
        $st->execute(['p' => $projetoId, 'c' => $codigo, 'p2' => $projetoId, 'c2' => $codigo,
            'nome' => $nome, 'caminho' => $caminho, 'tam' => $tamanho]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM arquivos WHERE id = ?')->execute([$id]);
    }

    /** Grava a nova ordem; $ids já deve ter sido validado contra a lista atual. */
    public function setOrder(int $projetoId, string $codigo, array $ids): void
    {
        $st = $this->pdo->prepare('UPDATE arquivos SET ordem = ? WHERE id = ? AND projeto_id = ? AND doc_codigo = ?');
        $this->pdo->beginTransaction();
        try {
            foreach (array_values($ids) as $i => $id) {
                $st->execute([$i + 1, $id, $projetoId, $codigo]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
