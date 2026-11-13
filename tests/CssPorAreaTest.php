<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use PHPUnit\Framework\TestCase;

/** CSS dividido por área (specs/css-por-area.md): cada tela carrega a base e só o CSS da própria área. */
final class CssPorAreaTest extends TestCase
{
    private int $pid;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Caxambu do Sul', 'SC')");
        $mid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'Obra')");
        $this->pid = (int) $pdo->lastInsertId();
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    /** @return string[] arquivos .css referenciados pelo HTML, na ordem */
    private function folhas(string $html): array
    {
        preg_match_all('#<link rel="stylesheet" href="/assets/([a-z0-9-]+\.css)">#', $html, $m);
        return $m[1];
    }

    public function testEachAreaLoadsBaseAndOnlyItsOwnCss(): void
    {
        $p = "/projetos/{$this->pid}";
        $casos = [
            '/projetos' => ['app.css', 'projetos.css'],
            '/municipios' => ['app.css', 'municipios.css'],
            "$p/balancete" => ['app.css', 'balancete.css'],
            '/guia-visual' => ['app.css', 'guia-visual.css'],
            // áreas sem arquivo próprio carregam só a base
            "$p/conciliacao" => ['app.css'],
            "$p/fisico-financeiro" => ['app.css'],
            "$p/relacao-de-bens" => ['app.css'],
            "$p/documentos/02" => ['app.css'],
            '/rota-que-nao-existe' => ['app.css'],
        ];
        foreach ($casos as $url => $esperado) {
            $this->assertSame($esperado, $this->folhas($this->get($url)->body), $url);
        }
    }

    public function testAreaCssFollowsTemplateNameConvention(): void
    {
        $this->assertSame('projetos', View::areaCss('projetos/index'));
        $this->assertSame('municipios', View::areaCss('municipios/form'));
        $this->assertSame('balancete', View::areaCss('balancete/_campos'));
        $this->assertSame('guia-visual', View::areaCss('guia_visual'));
        $this->assertNull(View::areaCss('conciliacao/index'), 'sem arquivo de área');
        $this->assertNull(View::areaCss('partes/pdf'));
        $this->assertNull(View::areaCss('erro'));
    }

    public function testAreaNameFromTemplateCannotEscapeAssetsFolder(): void
    {
        foreach (['../app', '..', 'app', 'a b', 'projetos.css', ''] as $nome) {
            $this->assertNull(View::areaCss($nome . '/x'), $nome);
        }
    }

    public function testAreaCssFilesExistAndHaveNoExternalImports(): void
    {
        foreach (['projetos', 'municipios', 'balancete', 'guia-visual'] as $area) {
            $arq = dirname(__DIR__) . "/public/assets/$area.css";
            $this->assertFileExists($arq);
            $this->assertStringNotContainsString('@import', (string) file_get_contents($arq));
        }
    }
}
