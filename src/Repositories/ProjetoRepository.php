<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjetoRepository
{
    private const CAMPOS = [
        'municipio_id', 'concedente', 'processo', 'nr_pp', 'nr_ne', 'nr_nl', 'objeto',
        'local', 'data_assinatura', 'prefeito_nome', 'contador_nome', 'contador_crc',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT p.*, m.nome AS municipio_nome, m.uf AS municipio_uf
             FROM projetos p JOIN municipios m ON m.id = p.municipio_id
             ORDER BY p.id DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT p.*, m.nome AS municipio_nome, m.uf AS municipio_uf
             FROM projetos p JOIN municipios m ON m.id = p.municipio_id WHERE p.id = ?'
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $cols = implode(', ', self::CAMPOS);
        $vals = ':' . implode(', :', self::CAMPOS);
        $st = $this->pdo->prepare("INSERT INTO projetos ($cols) VALUES ($vals)");
        $st->execute($this->bind($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", self::CAMPOS));
        $st = $this->pdo->prepare("UPDATE projetos SET $set WHERE id = :id");
        $st->execute($this->bind($d) + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM projetos WHERE id = ?')->execute([$id]);
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
