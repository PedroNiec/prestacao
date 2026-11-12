<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OverrideRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,int> campo => centavos */
    public function all(int $projetoId, string $relatorio): array
    {
        $st = $this->pdo->prepare('SELECT campo, valor_centavos FROM overrides WHERE projeto_id = ? AND relatorio = ?');
        $st->execute([$projetoId, $relatorio]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    public function set(int $projetoId, string $relatorio, string $campo, int $centavos): void
    {
        $this->pdo->prepare(
            'INSERT INTO overrides (projeto_id, relatorio, campo, valor_centavos) VALUES (?, ?, ?, ?)
             ON CONFLICT (projeto_id, relatorio, campo) DO UPDATE SET valor_centavos = excluded.valor_centavos'
        )->execute([$projetoId, $relatorio, $campo, $centavos]);
    }

    public function delete(int $projetoId, string $relatorio, string $campo): void
    {
        $this->pdo->prepare('DELETE FROM overrides WHERE projeto_id = ? AND relatorio = ? AND campo = ?')->execute([$projetoId, $relatorio, $campo]);
    }

    /** Remove sobrescritas cujas chaves não existem mais (ex.: lançamento excluído do balancete). */
    public function pruneExcept(int $projetoId, string $relatorio, array $chavesValidas): void
    {
        foreach (array_diff(array_keys($this->all($projetoId, $relatorio)), $chavesValidas) as $campo) {
            $this->delete($projetoId, $relatorio, $campo);
        }
    }
}
