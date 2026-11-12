<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MunicipioRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT m.*, (SELECT COUNT(*) FROM projetos p WHERE p.municipio_id = m.id) AS total_projetos
             FROM municipios m ORDER BY m.nome COLLATE NOCASE'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM municipios WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function countProjetos(int $id): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM projetos WHERE municipio_id = ?');
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }

    public function create(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO municipios (nome, uf, prefeito_nome, contador_nome, contador_crc)
             VALUES (:nome, :uf, :prefeito_nome, :contador_nome, :contador_crc)'
        );
        $st->execute($this->bind($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $st = $this->pdo->prepare(
            'UPDATE municipios SET nome = :nome, uf = :uf, prefeito_nome = :prefeito_nome,
             contador_nome = :contador_nome, contador_crc = :contador_crc WHERE id = :id'
        );
        $st->execute($this->bind($d) + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM municipios WHERE id = ?')->execute([$id]);
    }

    private function bind(array $d): array
    {
        return [
            'nome' => $d['nome'], 'uf' => $d['uf'], 'prefeito_nome' => $d['prefeito_nome'],
            'contador_nome' => $d['contador_nome'], 'contador_crc' => $d['contador_crc'],
        ];
    }
}
