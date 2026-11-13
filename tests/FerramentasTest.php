<?php
declare(strict_types=1);

namespace Tests;

use App\Services\Ferramentas;
use PHPUnit\Framework\TestCase;

/** Localização do Ghostscript e do LibreOffice (specs/suporte-windows.md, CA-W03 e CA-W04). */
final class FerramentasTest extends TestCase
{
    private const PF = ['ProgramFiles' => 'C:\\Program Files'];

    public function testEnvironmentVariablesWinEverywhere(): void
    {
        // CA-W03
        foreach (['Linux', 'Windows'] as $os) {
            $this->assertSame('/opt/gs', Ferramentas::ghostscript($os, ['PRESTACAO_GS' => '/opt/gs'], fn () => 'x', fn () => ['y']));
            $this->assertSame('/opt/lo', Ferramentas::libreOffice($os, ['PRESTACAO_SOFFICE' => '/opt/lo'], fn () => true));
        }
    }

    public function testLinuxKeepsTheOriginalNames(): void
    {
        // CA-W04: no Linux nada procura pastas do Windows
        $nunca = function () {
            $this->fail('Linux não deve procurar executáveis do Windows');
        };
        $this->assertSame('gs', Ferramentas::ghostscript('Linux', [], $nunca, $nunca));
        $this->assertSame('soffice', Ferramentas::libreOffice('Linux', [], $nunca));
    }

    public function testWindowsPrefersGswin64cOnPath(): void
    {
        $this->assertSame('D:\\tools\\gswin64c.exe', Ferramentas::ghostscript('Windows', self::PF, fn ($e) => $e === 'gswin64c.exe' ? 'D:\\tools\\gswin64c.exe' : null, fn () => []));
    }

    public function testWindowsPicksNewestInstalledGhostscript(): void
    {
        $procurado = null;
        $achados = ['C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe', 'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe', 'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe'];
        $gs = Ferramentas::ghostscript('Windows', self::PF, fn () => null, function ($padrao) use (&$procurado, $achados) {
            $procurado = $padrao;
            return $achados;
        });
        $this->assertSame('C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe', $gs);
        $this->assertSame('C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe', $procurado);
    }

    public function testWindowsFallsBackToGsWhenNothingIsInstalled(): void
    {
        $this->assertSame('gs', Ferramentas::ghostscript('Windows', self::PF, fn () => null, fn () => []));
        $this->assertSame('soffice', Ferramentas::libreOffice('Windows', self::PF, fn () => false));
    }

    public function testWindowsLibreOfficeDefaultFolder(): void
    {
        $this->assertSame('C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            Ferramentas::libreOffice('Windows', self::PF, fn ($p) => $p === 'C:\\Program Files\\LibreOffice\\program\\soffice.exe'));
        $this->assertSame('E:\\Apps\\LibreOffice\\program\\soffice.exe',
            Ferramentas::libreOffice('Windows', ['ProgramFiles' => 'E:\\Apps\\'], fn ($p) => true), 'respeita %ProgramFiles%');
    }

    public function testFileUrls(): void
    {
        $this->assertSame('file:///tmp/prestacao_lo_ab/perfil', Ferramentas::urlArquivo('/tmp/prestacao_lo_ab/perfil'));
        $this->assertSame('file:///C:/Users/pedro/AppData/Local/Temp/x/perfil', Ferramentas::urlArquivo('C:\\Users\\pedro\\AppData\\Local\\Temp/x/perfil'));
        $this->assertSame('file:///C:/Meus%20Documentos/x', Ferramentas::urlArquivo('C:\\Meus Documentos\\x'));
        $this->assertSame('C:/a/b.pdf', Ferramentas::caminhoGs('C:\\a\\b.pdf'));
        $this->assertSame('/tmp/b.pdf', Ferramentas::caminhoGs('/tmp/b.pdf'));
    }
}
