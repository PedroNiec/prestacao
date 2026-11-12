<?php
declare(strict_types=1);

namespace Tests;

use App\Services\FileStorage;
use App\Services\UploadException;
use PHPUnit\Framework\TestCase;

final class FileStorageTest extends TestCase
{
    private string $root;
    private FileStorage $fs;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/fs_test_' . bin2hex(random_bytes(4));
        mkdir($this->root);
        $this->fs = new FileStorage($this->root, false);
    }

    protected function tearDown(): void
    {
        $this->fs->deleteDir('p');
        @rmdir($this->root);
    }

    public function testStoresValidPdfWithGeneratedName(): void
    {
        $r = $this->fs->storeUpload(Support::pdf('Meu Arquivo.pdf'), 'p/1', ['pdf']);
        $this->assertSame('Meu Arquivo.pdf', $r['nome_original']);
        $this->assertMatchesRegularExpression('#^p/1/[a-f0-9]{32}\.pdf$#', $r['caminho']);
        $this->assertTrue($this->fs->exists($r['caminho']));
    }

    public function testRejectsWrongExtension(): void
    {
        $this->expectException(UploadException::class);
        $this->fs->storeUpload(Support::file(Support::PDF, 'doc.exe'), 'p/1', ['pdf']);
    }

    public function testRejectsFakePdfByContent(): void
    {
        $this->expectExceptionMessage('conteúdo');
        $this->fs->storeUpload(Support::file('<?php echo 1;', 'shell.pdf'), 'p/1', ['pdf']);
    }

    public function testRejectsRealPdfWithWrongExtension(): void
    {
        $this->expectException(UploadException::class);
        $this->fs->storeUpload(Support::file(Support::PDF, 'doc.php'), 'p/1', ['pdf']);
    }

    public function testRejectsDoubleExtensionTrick(): void
    {
        $this->expectException(UploadException::class);
        $this->fs->storeUpload(Support::file('<?php x;', 'shell.pdf.php'), 'p/1', ['pdf']);
    }

    public function testRejectsEmptyFile(): void
    {
        $this->expectExceptionMessage('vazio');
        $this->fs->storeUpload(Support::file('', 'a.pdf'), 'p/1', ['pdf']);
    }

    public function testRejectsUploadErrors(): void
    {
        $this->expectExceptionMessage('Selecione');
        $this->fs->storeUpload(['error' => UPLOAD_ERR_NO_FILE], 'p/1', ['pdf']);
    }

    public function testRejectsFileOverLimit(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'big');
        $h = fopen($tmp, 'w');
        fwrite($h, '%PDF-');
        ftruncate($h, FileStorage::MAX_BYTES + 1);
        fclose($h);
        try {
            $this->expectExceptionMessage('limite');
            $this->fs->storeUpload(['name' => 'big.pdf', 'tmp_name' => $tmp, 'error' => 0], 'p/1', ['pdf']);
        } finally {
            @unlink($tmp);
        }
    }

    public function testUserNameCannotInjectPath(): void
    {
        $r = $this->fs->storeUpload(Support::pdf('../../etc/passwd.pdf'), 'p/1', ['pdf']);
        $this->assertSame('passwd.pdf', $r['nome_original']);
        $this->assertStringStartsWith('p/1/', $r['caminho']);
    }

    public function testAbsoluteRejectsTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->fs->absolute('../outside.pdf');
    }

    public function testAbsoluteRejectsAbsolutePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->fs->absolute('/etc/passwd');
    }

    public function testDeleteDirRemovesEverything(): void
    {
        $r = $this->fs->storeUpload(Support::pdf(), 'p/9', ['pdf']);
        $this->fs->deleteDir('p/9');
        $this->assertFalse($this->fs->exists($r['caminho']));
    }
}
