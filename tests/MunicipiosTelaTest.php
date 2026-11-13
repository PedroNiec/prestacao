<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

/** Novo layout (menu lateral) e tela de Municípios (specs/redesenho-layout-municipios.md). */
final class MunicipiosTelaTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    private function municipio(string $nome, string $prefeito = '', string $contador = '', string $crc = ''): int
    {
        $pdo = Database::connection();
        $pdo->prepare("INSERT INTO municipios (nome, uf, prefeito_nome, contador_nome, contador_crc) VALUES (?, 'SC', ?, ?, ?)")
            ->execute([$nome, $prefeito, $contador, $crc]);
        return (int) $pdo->lastInsertId();
    }

    /** @return array<string,string> HTML de cada cartão, pelo nome */
    private function cartoes(string $html): array
    {
        preg_match_all('#<li class="cartao-mun">.*?</li>#s', $html, $m);
        $out = [];
        foreach ($m[0] as $li) {
            preg_match('#<h2 class="cartao-mun-nome">(.*?)</h2>#', $li, $n);
            $out[$n[1]] = $li;
        }
        return $out;
    }

    public function testSidebarLayoutOnEveryScreen(): void
    {
        // CA-L01
        foreach (['/projetos', '/municipios', '/municipios/novo', '/guia-visual'] as $url) {
            $html = $this->get($url)->body;
            $this->assertStringContainsString('<div class="app">', $html, $url);
            $this->assertStringContainsString('<header class="lateral">', $html, $url);
            $this->assertStringContainsString('<a class="lateral-rodape" href="/guia-visual">Guia visual</a>', $html, $url);
        }
        // o item ativo vem do caminho da requisição (no servidor, REQUEST_URI)
        $_SERVER['REQUEST_URI'] = '/municipios';
        try {
            $this->assertStringContainsString('<a href="/municipios" aria-current="page">Municípios</a>', $this->get('/municipios')->body);
        } finally {
            unset($_SERVER['REQUEST_URI']);
        }
    }

    public function testHeaderCountsSingularAndPlural(): void
    {
        $this->municipio('Chapecó');
        $this->assertStringContainsString('1 município cadastrado<', $this->get('/municipios')->body);
        $this->municipio('Xaxim');
        $html = $this->get('/municipios')->body;
        $this->assertStringContainsString('2 municípios cadastrados', $html);
        $this->assertStringContainsString('>+ Novo município</a>', $html);
    }

    public function testCardShowsInitialsNameUfProjectsAndSigners(): void
    {
        // CA-L02
        $mid = $this->municipio('Caxambu do Sul', 'Edi Marcos', 'Maicon Luft', 'CRC/SC 102.316');
        $this->municipio('Chapecó');
        Database::connection()->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'A'), ($mid, 'B')");
        $c = $this->cartoes($this->get('/municipios')->body);

        $this->assertStringContainsString('<span class="avatar" aria-hidden="true">CS</span>', $c['Caxambu do Sul']);
        $this->assertStringContainsString('<span class="item-sub">SC · 2 projeto(s)</span>', $c['Caxambu do Sul']);
        $this->assertStringContainsString('<dt>Prefeito</dt><dd>Edi Marcos</dd>', $c['Caxambu do Sul']);
        $this->assertStringContainsString('<dt>Contador</dt><dd>Maicon Luft <small>CRC/SC 102.316</small></dd>', $c['Caxambu do Sul']);
        $this->assertStringContainsString("href=\"/municipios/$mid/editar\"", $c['Caxambu do Sul']);

        $this->assertStringContainsString('>CH</span>', $c['Chapecó']);
        $this->assertStringContainsString('<dt>Prefeito</dt><dd>—</dd>', $c['Chapecó']);
    }

    public function testDeleteOnlyInsideMenuAndDisabledWhenThereAreProjects(): void
    {
        // CA-L03
        $com = $this->municipio('Caxambu do Sul');
        $sem = $this->municipio('Chapecó');
        Database::connection()->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($com, 'A')");
        $c = $this->cartoes($this->get('/municipios')->body);

        foreach ($c as $nome => $li) {
            $foraDoMenu = (string) preg_replace('#<details class="menu-mais">.*?</details>#s', '', $li);
            $this->assertStringNotContainsString('Excluir', $foraDoMenu, $nome);
            $this->assertStringContainsString('<summary aria-label="Mais ações para ' . $nome . ' - SC">⋮</summary>', $li);
        }
        $this->assertStringContainsString("action=\"/municipios/$sem/excluir\" onsubmit=\"return confirm('Excluir este município?')\"", $c['Chapecó']);
        $this->assertStringContainsString('name="_csrf"', $c['Chapecó']);

        $this->assertStringNotContainsString('/excluir', $c['Caxambu do Sul']);
        $this->assertStringContainsString('<button class="link perigo" type="button" disabled>Excluir</button>', $c['Caxambu do Sul']);
        $this->assertStringContainsString('Possui 1 projeto(s).', $c['Caxambu do Sul']);
    }

    public function testServerStillRefusesDeletingMunicipioWithProjects(): void
    {
        // RN-L01: o botão desabilitado é só conveniência; o bloqueio continua no servidor
        $mid = $this->municipio('Caxambu do Sul');
        Database::connection()->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'A')");
        handle(new Request('POST', "/municipios/$mid/excluir", [], ['_csrf' => \App\Core\Csrf::token()]), 'localhost');
        $this->assertSame(1, (int) Database::connection()->query("SELECT COUNT(*) FROM municipios WHERE id = $mid")->fetchColumn());
    }

    public function testEmptyStateAndEscaping(): void
    {
        $html = $this->get('/municipios')->body;
        $this->assertStringContainsString('class="estado-vazio estado-vazio--icone"', $html);
        $this->assertStringContainsString('0 municípios cadastrados', $html);

        $this->municipio('<script>x</script>', '<b>p</b>');
        $html = $this->get('/municipios')->body;
        $this->assertStringNotContainsString('<script>x</script>', $html);
        $this->assertStringNotContainsString('<b>p</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
    }
}
