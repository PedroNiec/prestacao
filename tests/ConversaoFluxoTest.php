<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FileStorage;
use App\Services\PdfMergeService;
use PHPUnit\Framework\TestCase;

final class ConversaoFluxoTest extends TestCase
{
    private int $pid;
    private array $tmp = [];

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

    protected function tearDown(): void
    {
        foreach ($this->tmp as $f) {
            @unlink($f);
        }
    }

    private function enviar(string $codigo, array $file): Response
    {
        return handle(new Request('POST', "/projetos/{$this->pid}/documentos/$codigo/enviar", [], ['_csrf' => Csrf::token()], ['arquivo' => $file]), 'localhost');
    }

    private function doc(string $codigo): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM documentos WHERE projeto_id = ? AND doc_codigo = ?');
        $st->execute([$this->pid, $codigo]);
        return $st->fetch() ?: null;
    }

    private function abs(string $rel): string
    {
        return FileStorage::default()->absolute($rel);
    }

    private function arquivosEmDisco(): array
    {
        $dir = $this->abs('projetos/' . $this->pid);
        if (!is_dir($dir)) {
            return [];
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        $out = [];
        foreach ($it as $f) {
            $out[] = str_replace($dir . '/', '', $f->getPathname());
        }
        sort($out);
        return $out;
    }

    private function png(string $nome = 'placa.png'): array
    {
        return Support::fromPath($this->tmp[] = Support::png(800, 500), $nome);
    }

    public function testPngUploadIsStoredAsPdfOnly(): void
    {
        $this->enviar('16', $this->png());
        $d = $this->doc('16');
        $this->assertNotNull($d);
        $this->assertSame('placa.png', $d['nome_original']);
        $this->assertStringEndsWith('.pdf', $d['caminho']);
        $this->assertSame(1, (new PdfMergeService())->countPages($this->abs($d['caminho'])));
        $this->assertCount(1, $this->arquivosEmDisco(), 'o PNG original não deve ficar no disco');
    }

    public function testPngDownloadIsPdfWithCatalogName(): void
    {
        $this->enviar('16', $this->png());
        $res = handle(new Request('GET', "/projetos/{$this->pid}/documentos/16/baixar"), 'localhost');
        $this->assertSame('application/pdf', $res->headers['Content-Type']);
        $this->assertStringContainsString('16-identificacao-oficial.pdf', $res->headers['Content-Disposition']);
    }

    public function testReplacingConvertedDocumentRemovesOldPdf(): void
    {
        $this->enviar('16', $this->png('a.png'));
        $antigo = $this->doc('16')['caminho'];
        $this->enviar('16', $this->png('b.png'));
        $this->assertFileDoesNotExist($this->abs($antigo));
        $this->assertSame('b.png', $this->doc('16')['nome_original']);
        $this->assertCount(1, $this->arquivosEmDisco());
    }

    public function testPdfIsRejectedForPngDocument(): void
    {
        $this->enviar('16', Support::pdf('doc.pdf'));
        $this->assertNull($this->doc('16'));
        $flash = Session::pullFlash();
        $this->assertStringContainsString('PNG', end($flash)['mensagem']);
    }

    public function testFakePngIsRejectedByContent(): void
    {
        $this->enviar('16', Support::file('nao sou png', 'fake.png'));
        $this->assertNull($this->doc('16'));
    }

    public function testPngIsRejectedForNormalUploadDocument(): void
    {
        $this->enviar('18', $this->png());
        $this->assertNull($this->doc('18'));
    }

    public function testPptxUploadConvertsToPdf(): void
    {
        if (!Support::soffice()) {
            $this->markTestSkipped('LibreOffice não instalado');
        }
        $this->enviar('08', Support::fromPath(Support::pptx(), 'Acervo Fotográfico.pptx'));
        $d = $this->doc('08');
        $this->assertNotNull($d);
        $this->assertSame('Acervo Fotográfico.pptx', $d['nome_original']);
        $this->assertSame(2, (new PdfMergeService())->countPages($this->abs($d['caminho'])));
        $this->assertCount(1, $this->arquivosEmDisco());
    }

    public function testFailedPptxConversionKeepsPreviousDocumentAndLeavesNoGarbage(): void
    {
        if (!Support::soffice()) {
            $this->markTestSkipped('LibreOffice não instalado');
        }
        $this->enviar('08', Support::fromPath(Support::pptx(), 'bom.pptx'));
        $antes = $this->doc('08');

        // ZIP válido (passa pelo tipo de arquivo), mas não é um PowerPoint.
        $zip = $this->tmp[] = tempnam(sys_get_temp_dir(), 'zip');
        $z = new \ZipArchive();
        $z->open($zip, \ZipArchive::OVERWRITE);
        $z->addFromString('qualquer.txt', 'x');
        $z->close();
        Session::pullFlash();
        $this->enviar('08', Support::fromPath($zip, 'ruim.pptx'));

        $this->assertSame($antes['caminho'], $this->doc('08')['caminho']);
        $this->assertFileExists($this->abs($antes['caminho']));
        $this->assertCount(1, $this->arquivosEmDisco());
        $flash = Session::pullFlash();
        $this->assertSame('erro', end($flash)['tipo']);
    }

    public function testProjectPageShowsConvertedStatusAndExtensions(): void
    {
        $this->enviar('16', $this->png());
        $body = handle(new Request('GET', "/projetos/{$this->pid}"), 'localhost')->body;
        $this->assertStringContainsString('Convertido', $body);
        $this->assertStringContainsString('image/png,.png', $body);
        $this->assertStringContainsString('.pptx', $body);
    }

    public function testDeletingProjectRemovesConvertedFiles(): void
    {
        $this->enviar('16', $this->png());
        handle(new Request('POST', "/projetos/{$this->pid}/excluir", [], ['_csrf' => Csrf::token()]), 'localhost');
        $this->assertDirectoryDoesNotExist($this->abs('projetos/' . $this->pid));
    }
}
