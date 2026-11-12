<?php
declare(strict_types=1);

namespace Tests;

use App\Catalog\DocumentCatalog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class CadastrosTest extends TestCase
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
        return handle(new Request('GET', $path), 'localhost:8000');
    }

    private function post(string $path, array $data = []): Response
    {
        return handle(new Request('POST', $path, [], $data + ['_csrf' => Csrf::token()]), 'localhost:8000');
    }

    private function criarMunicipio(): int
    {
        $res = $this->post('/municipios', [
            'nome' => 'Caxambu do Sul', 'uf' => 'SC', 'prefeito_nome' => 'Prefeito A',
            'contador_nome' => 'Contador B', 'contador_crc' => 'CRC/SC 1',
        ]);
        $this->assertSame(303, $res->status);
        return (int) Database::connection()->query('SELECT id FROM municipios')->fetchColumn();
    }

    public function testCatalogHas25DocumentsWithUniqueFileNames(): void
    {
        $docs = DocumentCatalog::all();
        $this->assertCount(25, $docs);
        $this->assertCount(25, array_unique(array_column($docs, 'arquivo')));
        $this->assertArrayHasKey('10a', $docs);
        $this->assertArrayHasKey('10b', $docs);
    }

    public function testCatalogBehaviorsMatchSpec(): void
    {
        $por = [];
        foreach (DocumentCatalog::all() as $d) {
            $por[$d['comportamento']][] = $d['codigo'];
        }
        $this->assertSame(['02','03','04','05','06','07','13','14'], $por['merge']);
        $this->assertSame(['10a','10b','11','12','17','18','19','20','21','23','24'], $por['upload']);
        $this->assertSame(['01','09','15','22'], $por['report']);
        $this->assertSame(['08'], $por['convert_pptx']);
        $this->assertSame(['16'], $por['convert_png']);
    }

    public function testMunicipioInvalidDoesNotSave(): void
    {
        $res = $this->post('/municipios', ['nome' => '', 'uf' => 'SC']);
        $this->assertSame('/municipios/novo', $res->headers['Location']);
        $this->assertSame(0, (int) Database::connection()->query('SELECT COUNT(*) FROM municipios')->fetchColumn());
    }

    public function testCreateProjetoInheritsAndShowsAll25Documents(): void
    {
        $mid = $this->criarMunicipio();
        $res = $this->post('/projetos', ['municipio_id' => $mid, 'objeto' => 'Ônibus escolar']);
        $this->assertSame(303, $res->status);

        $row = Database::connection()->query('SELECT * FROM projetos')->fetch();
        $this->assertSame('Prefeito A', $row['prefeito_nome']);

        $page = $this->get('/projetos/' . $row['id']);
        $this->assertSame(200, $page->status);
        $this->assertSame(25, substr_count($page->body, 'class="tag vazio"'));
        $this->assertStringContainsString('Ônibus escolar', $page->body);
    }

    public function testDeleteMunicipioWithProjetosIsBlocked(): void
    {
        $mid = $this->criarMunicipio();
        $this->post('/projetos', ['municipio_id' => $mid, 'objeto' => 'X']);

        $this->post("/municipios/$mid/excluir");
        $this->assertSame(1, (int) Database::connection()->query('SELECT COUNT(*) FROM municipios')->fetchColumn());
        $flash = Session::pullFlash();
        $this->assertSame('erro', end($flash)['tipo']);
    }

    public function testDeleteMunicipioWithoutProjetosWorks(): void
    {
        $mid = $this->criarMunicipio();
        $this->post("/municipios/$mid/excluir");
        $this->assertSame(0, (int) Database::connection()->query('SELECT COUNT(*) FROM municipios')->fetchColumn());
    }

    public function testEditProjetoKeepsSnapshotWhenMunicipioChanges(): void
    {
        $mid = $this->criarMunicipio();
        $this->post('/projetos', ['municipio_id' => $mid, 'objeto' => 'X']);
        $this->post("/municipios/$mid", ['nome' => 'Caxambu do Sul', 'uf' => 'SC', 'prefeito_nome' => 'Novo Prefeito']);
        $p = Database::connection()->query('SELECT prefeito_nome FROM projetos')->fetchColumn();
        $this->assertSame('Prefeito A', $p);
    }

    public function testNewProjetoWithoutMunicipiosRedirects(): void
    {
        $res = $this->get('/projetos/novo');
        $this->assertSame('/municipios/novo', $res->headers['Location']);
    }

    public function testProjetoNotFound(): void
    {
        $this->assertSame(404, $this->get('/projetos/999')->status);
        $this->assertSame(404, $this->post('/projetos/999/excluir')->status);
    }

    public function testStoredXssIsEscaped(): void
    {
        $mid = $this->criarMunicipio();
        $this->post('/projetos', ['municipio_id' => $mid, 'objeto' => '<script>alert(1)</script>']);
        $id = Database::connection()->query('SELECT id FROM projetos')->fetchColumn();
        $body = $this->get("/projetos/$id")->body;
        $this->assertStringNotContainsString('<script>alert(1)', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }
}
