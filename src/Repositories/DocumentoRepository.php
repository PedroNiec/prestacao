<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DocumentoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $projetoId, string $codigo): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM documentos WHERE projeto_id = ? AND doc_codigo = ?');
        $st->execute([$projetoId, $codigo]);
        return $st->fetch() ?: null;
    }

    /** @return array<string,array<string,mixed>> indexado por código do documento */
    public function byProjeto(int $projetoId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM documentos WHERE projeto_id = ?');
        $st->execute([$projetoId]);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[$row['doc_codigo']] = $row;
        }
        return $out;
    }

    public function save(int $projetoId, string $codigo, string $caminho, string $nomeOriginal, int $tamanho): void
    {
        $st = $this->pdo->prepare(
            "INSERT INTO documentos (projeto_id, doc_codigo, caminho, nome_original, tamanho)
             VALUES (:p, :c, :caminho, :nome, :tam)
             ON CONFLICT (projeto_id, doc_codigo) DO UPDATE SET
                caminho = excluded.caminho, nome_original = excluded.nome_original,
                tamanho = excluded.tamanho, desatualizado = 0, atualizado_em = CURRENT_TIMESTAMP"
        );
        $st->execute(['p' => $projetoId, 'c' => $codigo, 'caminho' => $caminho, 'nome' => $nomeOriginal, 'tam' => $tamanho]);
    }

    public function markOutdated(int $projetoId, string $codigo): void
    {
        $this->pdo->prepare('UPDATE documentos SET desatualizado = 1 WHERE projeto_id = ? AND doc_codigo = ?')
            ->execute([$projetoId, $codigo]);
    }

    public function delete(int $projetoId, string $codigo): void
    {
        $this->pdo->prepare('DELETE FROM documentos WHERE projeto_id = ? AND doc_codigo = ?')->execute([$projetoId, $codigo]);
    }
}
