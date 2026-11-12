<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Core\Migrator;
use PDOException;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private function migrated(): \PDO
    {
        $pdo = Database::open(':memory:');
        (new Migrator($pdo, dirname(__DIR__) . '/database/migrations'))->run();
        return $pdo;
    }

    public function testAppliesOnlyOnce(): void
    {
        $pdo = Database::open(':memory:');
        $m = new Migrator($pdo, dirname(__DIR__) . '/database/migrations');
        $this->assertNotEmpty($m->run());
        $this->assertSame([], $m->run());
    }

    public function testCannotDeleteMunicipioWithProjetos(): void
    {
        $pdo = $this->migrated();
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Teste', 'SC')");
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES (1, 'X')");
        $this->expectException(PDOException::class);
        $pdo->exec('DELETE FROM municipios WHERE id = 1');
    }
}
