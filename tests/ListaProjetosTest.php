<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

/** Lista de Projetos (specs/redesenho-lista-projetos.md). */
final class ListaProjetosTest extends TestCase
{
    private int $mid;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Caxambu do Sul', 'SC')");
        $this->mid = (int) $pdo->lastInsertId();
    }

    private function pagina(): string
    {
        return handle(new Request('GET', '/projetos'), 'localhost')->body;
    }

    private function projeto(string $objeto, string $processo = ''): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO projetos (municipio_id, objeto, processo) VALUES (?, ?, ?)')->execute([$this->mid, $objeto, $processo]);
        return (int) $pdo->lastInsertId();
    }

    /** @return string[] HTML de cada cartão */
    private function cartoes(string $html): array
    {
        preg_match_all('#<li class="cartao-projeto">.*?</li>#s', $html, $m);
        return $m[0];
    }

    public function testHeaderCountsSingularAndPlural(): void
    {
        // CA-LP01
        $this->projeto('A');
        $html = $this->pagina();
        $this->assertStringContainsString('<p class="projeto-sub">1 projeto</p>', $html);
        $this->assertStringContainsString('<a class="btn" href="/projetos/novo">+ Novo projeto</a>', $html);
        $this->projeto('B');
        $this->assertStringContainsString('<p class="projeto-sub">2 projetos</p>', $this->pagina());
    }

    public function testCardHasIconObjectSubtitleAndSingleLink(): void
    {
        // CA-LP02 e CA-LP03
        $pid = $this->projeto('AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', 'PROCESSO SCC 7909/2025');
        [$c] = $this->cartoes($this->pagina());
        $this->assertStringContainsString('<span class="icone-pasta" aria-hidden="true"></span>', $c);
        $this->assertStringContainsString("<a class=\"projeto-link\" href=\"/projetos/$pid\">AQUISIÇÃO DE UM VEÍCULO ÔNIBUS</a>", $c);
        $this->assertStringContainsString("<span class=\"item-sub\">Caxambu do Sul - SC · PROCESSO SCC 7909/2025 · #$pid</span>", $c);
        $this->assertStringContainsString('<span class="cartao-projeto-abrir" aria-hidden="true">Abrir ›</span>', $c);
        $this->assertSame(1, substr_count($c, '<a '), 'um único link por projeto');
    }

    public function testWithoutProcessOrObject(): void
    {
        $pid = $this->projeto('');
        [$c] = $this->cartoes($this->pagina());
        $this->assertStringContainsString("<span class=\"item-sub\">Caxambu do Sul - SC · #$pid</span>", $c);
        $this->assertStringContainsString(">Projeto #$pid</a>", $c);
    }

    public function testEmptyStateOffersTheRightAction(): void
    {
        // CA-LP04
        $html = $this->pagina();
        $this->assertStringContainsString('class="estado-vazio estado-vazio--pasta"', $html);
        $this->assertStringContainsString('<a class="btn" href="/projetos/novo">Novo projeto</a>', $html);

        Database::connection()->exec('DELETE FROM municipios');
        $html = $this->pagina();
        $this->assertStringContainsString('<a class="btn" href="/municipios/novo">Cadastrar município</a>', $html);
        $this->assertStringContainsString('0 projetos', $html);
    }

    public function testEscapesUserText(): void
    {
        $this->projeto('<script>x</script>', '<b>p</b>');
        $html = $this->pagina();
        $this->assertStringNotContainsString('<script>x</script>', $html);
        $this->assertStringNotContainsString('<b>p</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
    }
}
