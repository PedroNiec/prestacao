<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/** Executa migrações SQL numeradas (database/migrations/NNN_nome.sql) uma única vez. */
final class Migrator
{
    public function __construct(private PDO $pdo, private string $dir)
    {
    }

    /** @return string[] migrações aplicadas nesta execução */
    public function run(): array
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            versao TEXT PRIMARY KEY,
            aplicada_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        $done = $this->pdo->query('SELECT versao FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(rtrim($this->dir, '/') . '/*.sql') ?: [];
        sort($files);

        $applied = [];
        foreach ($files as $file) {
            $version = basename($file, '.sql');
            if (in_array($version, $done, true)) {
                continue;
            }
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException("Não foi possível ler a migração $file");
            }
            $this->pdo->beginTransaction();
            try {
                $this->pdo->exec($sql);
                $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (versao) VALUES (?)');
                $stmt->execute([$version]);
                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw new RuntimeException("Falha na migração $version: " . $e->getMessage(), 0, $e);
            }
            $applied[] = $version;
        }
        return $applied;
    }
}
