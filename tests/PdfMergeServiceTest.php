<?php
declare(strict_types=1);

namespace Tests;

use App\Services\PdfMergeException;
use App\Services\PdfMergeService;
use PHPUnit\Framework\TestCase;

final class PdfMergeServiceTest extends TestCase
{
    private array $tmp = [];

    protected function tearDown(): void
    {
        foreach ($this->tmp as $f) {
            @unlink($f);
        }
    }

    private function pdf(string $r, int $n = 1, ?string $senha = null): string
    {
        return $this->tmp[] = Support::realPdf($r, $n, $senha);
    }

    private function saida(): string
    {
        return $this->tmp[] = sys_get_temp_dir() . '/merge_' . bin2hex(random_bytes(6)) . '.pdf';
    }

    public function testGhostscriptIsAvailable(): void
    {
        $this->assertTrue((new PdfMergeService())->available());
    }

    public function testCountPages(): void
    {
        $s = new PdfMergeService();
        $this->assertSame(3, $s->countPages($this->pdf('P', 3)));
        $this->assertNull($s->countPages($this->pdf('P', 1, 'senha')), 'protegido por senha');
        $lixo = $this->tmp[] = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($lixo, "%PDF-1.4\nlixo");
        $this->assertNull($s->countPages($lixo), 'corrompido');
    }

    public function testCountPagesHandlesParenthesesAndSpacesInPath(): void
    {
        $dir = sys_get_temp_dir() . '/dir com (parenteses) ' . bin2hex(random_bytes(3));
        mkdir($dir);
        $dest = $dir . '/arquivo (1).pdf';
        copy($this->pdf('P', 2), $dest);
        $this->tmp[] = $dest;
        $this->assertSame(2, (new PdfMergeService())->countPages($dest));
        @unlink($dest);
        @rmdir($dir);
    }

    public function testMergeSumsPages(): void
    {
        $out = $this->saida();
        (new PdfMergeService())->merge([
            ['path' => $this->pdf('A', 2), 'nome' => 'a.pdf'],
            ['path' => $this->pdf('B', 3), 'nome' => 'b.pdf'],
        ], $out);
        $this->assertSame(5, (new PdfMergeService())->countPages($out));
    }

    public function testMergeRefusesProtectedFileAndCreatesNothing(): void
    {
        $out = $this->saida();
        try {
            (new PdfMergeService())->merge([
                ['path' => $this->pdf('A'), 'nome' => 'a.pdf'],
                ['path' => $this->pdf('S', 1, 'senha'), 'nome' => 'protegido.pdf'],
            ], $out);
            $this->fail('Deveria ter recusado');
        } catch (PdfMergeException $e) {
            $this->assertStringContainsString('protegido.pdf', $e->getMessage());
            $this->assertFileDoesNotExist($out);
        }
    }

    public function testMergeWithoutFilesFails(): void
    {
        $this->expectException(PdfMergeException::class);
        (new PdfMergeService())->merge([], $this->saida());
    }

    public function testMissingGhostscriptGivesClearError(): void
    {
        $this->expectExceptionMessage('Ghostscript');
        (new PdfMergeService('/caminho/inexistente/gs'))->merge([['path' => $this->pdf('A'), 'nome' => 'a.pdf']], $this->saida());
    }
}
