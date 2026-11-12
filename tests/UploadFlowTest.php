<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FileStorage;
use PHPUnit\Framework\TestCase;

final class UploadFlowTest extends TestCase
{
    private int $pid;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('M', 'SC')");
        $mid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'Obj')");
        $this->pid = (int) $pdo->lastInsertId();
    }

    private function enviar(string $codigo, array $file, ?int $pid = null): Response
    {
        $pid ??= $this->pid;
        return handle(new Request('POST', "/projetos/$pid/documentos/$codigo/enviar", [], ['_csrf' => Csrf::token()], ['arquivo' => $file]), 'localhost');
    }

    private function post(string $path): Response
    {
        return handle(new Request('POST', $path, [], ['_csrf' => Csrf::token()]), 'localhost');
    }

    private function get(string $path, array $query = []): Response
    {
        return handle(new Request('GET', $path, $query), 'localhost');
    }

    private function registro(string $codigo): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM documentos WHERE projeto_id = ? AND doc_codigo = ?');
        $st->execute([$this->pid, $codigo]);
        return $st->fetch() ?: null;
    }

    private function abs(string $rel): string
    {
        return FileStorage::default()->absolute($rel);
    }

    public function testUploadStoresFileAndShowsEnviado(): void
    {
        $this->enviar('18', Support::pdf('parecer.pdf'));
        $reg = $this->registro('18');
        $this->assertNotNull($reg);
        $this->assertSame('parecer.pdf', $reg['nome_original']);
        $this->assertFileExists($this->abs($reg['caminho']));

        $page = $this->get("/projetos/{$this->pid}")->body;
        $this->assertSame(1, substr_count($page, 'class="tag ok"'));
        $this->assertStringContainsString('parecer.pdf', $page);
    }

    public function testReplaceRemovesOldFile(): void
    {
        $this->enviar('19', Support::pdf('a.pdf'));
        $antigo = $this->registro('19')['caminho'];
        $this->enviar('19', Support::pdf('b.pdf'));
        $novo = $this->registro('19');
        $this->assertSame('b.pdf', $novo['nome_original']);
        $this->assertFileDoesNotExist($this->abs($antigo));
        $this->assertFileExists($this->abs($novo['caminho']));
        $this->assertSame(1, (int) Database::connection()->query("SELECT COUNT(*) FROM documentos WHERE doc_codigo='19'")->fetchColumn());
    }

    public function testRemoveDeletesRecordAndFile(): void
    {
        $this->enviar('20', Support::pdf());
        $caminho = $this->registro('20')['caminho'];
        $this->post("/projetos/{$this->pid}/documentos/20/remover");
        $this->assertNull($this->registro('20'));
        $this->assertFileDoesNotExist($this->abs($caminho));
    }

    public function testInvalidFileIsRejectedWithMessage(): void
    {
        $this->enviar('21', Support::file('texto qualquer', 'x.pdf'));
        $this->assertNull($this->registro('21'));
        $flash = Session::pullFlash();
        $this->assertSame('erro', end($flash)['tipo']);
    }

    public function testNonUploadDocumentRejectsUpload(): void
    {
        $this->enviar('03', Support::pdf());
        $this->assertNull($this->registro('03'));
    }

    public function testUnknownDocumentAndProjectReturn404(): void
    {
        $this->assertSame(404, $this->enviar('99', Support::pdf())->status);
        $this->assertSame(404, $this->enviar('18', Support::pdf(), 9999)->status);
        $this->assertSame(404, $this->get('/projetos/' . $this->pid . '/documentos/..%2f..%2fx/baixar')->status);
    }

    public function testDownloadServesRegisteredPdf(): void
    {
        $this->enviar('23', Support::pdf());
        $res = $this->get("/projetos/{$this->pid}/documentos/23/baixar");
        $this->assertSame(200, $res->status);
        $this->assertSame('application/pdf', $res->headers['Content-Type']);
        $this->assertStringContainsString('attachment', $res->headers['Content-Disposition']);
        $this->assertStringContainsString('23-termo-de-aceitacao-definitiva.pdf', $res->headers['Content-Disposition']);
        $this->assertSame('nosniff', $res->headers['X-Content-Type-Options']);
    }

    public function testInlineViewUsesInlineDisposition(): void
    {
        $this->enviar('24', Support::pdf());
        $res = $this->get("/projetos/{$this->pid}/documentos/24/baixar", ['ver' => '1']);
        $this->assertStringStartsWith('inline', $res->headers['Content-Disposition']);
    }

    public function testDownloadWithoutFileIs404(): void
    {
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/documentos/17/baixar")->status);
    }

    public function testDeletingProjectRemovesItsFiles(): void
    {
        $this->enviar('18', Support::pdf());
        $this->enviar('19', Support::pdf());
        $c = $this->registro('18')['caminho'];
        $this->post("/projetos/{$this->pid}/excluir");
        $this->assertFileDoesNotExist($this->abs($c));
        $this->assertSame(0, (int) Database::connection()->query('SELECT COUNT(*) FROM documentos')->fetchColumn());
        $this->assertDirectoryDoesNotExist($this->abs('projetos/' . $this->pid));
    }

    public function testUploadWithoutCsrfIsRejected(): void
    {
        $res = handle(new Request('POST', "/projetos/{$this->pid}/documentos/18/enviar", [], [], ['arquivo' => Support::pdf()]), 'localhost');
        $this->assertSame(419, $res->status);
        $this->assertNull($this->registro('18'));
    }
}
