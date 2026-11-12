<?php
declare(strict_types=1);

namespace Tests;

use App\Services\ConversionService;
use App\Services\PdfMergeService;
use App\Services\UploadException;
use PHPUnit\Framework\TestCase;

final class ConversionServiceTest extends TestCase
{
    private array $tmp = [];

    protected function tearDown(): void
    {
        foreach ($this->tmp as $f) {
            @unlink($f);
        }
    }

    private function svc(?string $soffice = null): ConversionService
    {
        return new ConversionService(new PdfMergeService(), $soffice);
    }

    private function salvar(string $bytes): string
    {
        $f = $this->tmp[] = tempnam(sys_get_temp_dir(), 'out');
        file_put_contents($f, $bytes);
        return $f;
    }

    private function mediaBox(string $bytes): array
    {
        $this->assertSame(1, preg_match('#/MediaBox\s*\[\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*\]#', $bytes, $m));
        return [(float) $m[1], (float) $m[2]];
    }

    public function testPngLandscapeBecomesOneLandscapePage(): void
    {
        $png = $this->tmp[] = Support::png(1200, 600);
        $pdf = $this->svc()->pngToPdf($png);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, (new PdfMergeService())->countPages($this->salvar($pdf)));
        [$w, $h] = $this->mediaBox($pdf);
        $this->assertGreaterThan($h, $w);
    }

    public function testPngPortraitBecomesOnePortraitPage(): void
    {
        $png = $this->tmp[] = Support::png(600, 1200);
        $pdf = $this->svc()->pngToPdf($png);
        $this->assertSame(1, (new PdfMergeService())->countPages($this->salvar($pdf)));
        [$w, $h] = $this->mediaBox($pdf);
        $this->assertLessThan($h, $w);
    }

    public function testVeryTallPngDoesNotOverflowToSecondPage(): void
    {
        $png = $this->tmp[] = Support::png(300, 3000);
        $this->assertSame(1, (new PdfMergeService())->countPages($this->salvar($this->svc()->pngToPdf($png))));
    }

    public function testLargePhotoSizedPngConverts(): void
    {
        $png = $this->tmp[] = Support::png(3000, 2000);
        $this->assertSame(1, (new PdfMergeService())->countPages($this->salvar($this->svc()->pngToPdf($png))));
    }

    public function testTransparentPngConverts(): void
    {
        $png = $this->tmp[] = Support::png(400, 300, true);
        $this->assertSame(1, (new PdfMergeService())->countPages($this->salvar($this->svc()->pngToPdf($png))));
    }

    public function testCorruptedPngIsRejected(): void
    {
        $f = $this->tmp[] = tempnam(sys_get_temp_dir(), 'bad');
        file_put_contents($f, "\x89PNG\r\n\x1a\nlixo");
        $this->expectException(UploadException::class);
        $this->svc()->pngToPdf($f);
    }

    public function testPngWithHugeDimensionsIsRejected(): void
    {
        $img = imagecreate(9000, 9000); // 81 MP, mas comprime para quase nada
        imagecolorallocate($img, 255, 255, 255);
        $f = $this->tmp[] = tempnam(sys_get_temp_dir(), 'big');
        imagepng($img, $f);
        $this->expectExceptionMessage('grande demais');
        $this->svc()->pngToPdf($f);
    }

    public function testPptxConvertsAllSlidesInOrder(): void
    {
        if (!Support::soffice()) {
            $this->markTestSkipped('LibreOffice não instalado');
        }
        $pdf = $this->svc()->pptxToPdf(Support::pptx());
        $path = $this->salvar($pdf);
        $this->assertSame(2, (new PdfMergeService())->countPages($path));
        $texto = Support::texto($path);
        $this->assertLessThan(strpos($texto, 'SLIDE-DOIS'), strpos($texto, 'SLIDE-UM'));
    }

    public function testPptxConversionCleansUpTempFolders(): void
    {
        if (!Support::soffice()) {
            $this->markTestSkipped('LibreOffice não instalado');
        }
        $antes = count(Support::sobrasDeConversao());
        $this->svc()->pptxToPdf(Support::pptx());
        $this->assertCount($antes, Support::sobrasDeConversao());
    }

    public function testFakePptxFailsWithClearMessageAndCleansUp(): void
    {
        if (!Support::soffice()) {
            $this->markTestSkipped('LibreOffice não instalado');
        }
        $f = $this->tmp[] = tempnam(sys_get_temp_dir(), 'fake');
        file_put_contents($f, 'isto nao e um pptx');
        $antes = count(Support::sobrasDeConversao());
        try {
            $this->svc()->pptxToPdf($f);
            $this->fail('Deveria falhar');
        } catch (UploadException $e) {
            $this->assertStringContainsString('Não foi possível converter', $e->getMessage());
        }
        $this->assertCount($antes, Support::sobrasDeConversao());
    }

    public function testMissingLibreOfficeGivesInstallHint(): void
    {
        $this->expectExceptionMessage('LibreOffice não está instalado');
        $this->svc('/caminho/inexistente/soffice')->pptxToPdf(Support::pptx());
    }
}
