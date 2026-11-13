<?php
declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Converte PNG e PowerPoint em PDF.
 *
 * ATENÇÃO: o LibreOffice retorna código 0 mesmo quando não consegue abrir o arquivo
 * ("source file could not be loaded"); o resultado é validado pela existência e pela
 * contagem de páginas do PDF gerado.
 */
final class ConversionService
{
    private const MAX_PIXELS = 60_000_000;
    // A4 em pontos, com margem de 10 mm.
    private const PAGE_W = 595.28;
    private const PAGE_H = 841.89;
    private const MARGIN = 28.35;

    public function __construct(
        private PdfMergeService $pdf,
        private ?string $soffice = null,
        private int $timeout = 180,
    ) {
        $this->soffice ??= Ferramentas::libreOffice();   // Linux: soffice; Windows: Program Files\LibreOffice (specs/suporte-windows.md)
    }

    /** @throws UploadException */
    public function pngToPdf(string $pngPath): string
    {
        $info = @getimagesize($pngPath);
        if ($info === false || $info[2] !== IMAGETYPE_PNG) {
            throw new UploadException('A imagem PNG está corrompida ou é inválida.');
        }
        [$w, $h] = $info;
        if ($w < 1 || $h < 1 || $w * $h > self::MAX_PIXELS) {
            throw new UploadException('A imagem é grande demais (máximo de ' . number_format(self::MAX_PIXELS / 1_000_000, 0) . ' megapixels).');
        }

        $paisagem = $w > $h;
        $areaW = ($paisagem ? self::PAGE_H : self::PAGE_W) - 2 * self::MARGIN;
        $areaH = ($paisagem ? self::PAGE_W : self::PAGE_H) - 2 * self::MARGIN;
        $escala = min($areaW / $w, $areaH / $h) * 0.98; // folga para não gerar página extra
        $largura = round($w * $escala, 2);
        $altura = round($h * $escala, 2);

        $dados = base64_encode((string) file_get_contents($pngPath));
        $html = '<html><head><meta charset="utf-8"><style>@page{margin:' . self::MARGIN . 'pt}body{margin:0}'
            . '.c{text-align:center}</style></head><body><div class="c">'
            . '<img src="data:image/png;base64,' . $dados . '" style="width:' . $largura . 'pt;height:' . $altura . 'pt"></div></body></html>';

        $limite = ini_get('memory_limit');
        ini_set('memory_limit', '768M');
        try {
            $opt = new Options();
            $opt->set('isRemoteEnabled', false);
            $opt->set('isPhpEnabled', false);
            $dompdf = new Dompdf($opt);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', $paisagem ? 'landscape' : 'portrait');
            $dompdf->render();
            $saida = $dompdf->output();
        } catch (\Throwable $e) {
            throw new UploadException('Não foi possível converter a imagem em PDF.');
        } finally {
            ini_set('memory_limit', (string) $limite);
        }
        return $saida;
    }

    /** @throws UploadException */
    public function pptxToPdf(string $pptxPath): string
    {
        // O LibreOffice 26.x (Windows) converte qualquer arquivo em PDF, até texto renomeado para .pptx;
        // por isso o próprio conversor confere que é uma apresentação de verdade (specs/suporte-windows.md).
        if (!self::ehPptx($pptxPath)) {
            throw new UploadException('Não foi possível converter o PowerPoint. O arquivo pode estar corrompido ou não é uma apresentação .pptx.');
        }
        $tmp = sys_get_temp_dir() . '/prestacao_lo_' . bin2hex(random_bytes(8));
        if (!mkdir($tmp . '/perfil', 0700, true)) {
            throw new UploadException('Não foi possível criar a pasta temporária de conversão.');
        }
        try {
            $entrada = $tmp . '/entrada.pptx';
            if (!copy($pptxPath, $entrada)) {
                throw new UploadException('Não foi possível preparar o arquivo para conversão.');
            }
            $r = $this->run([
                '--headless', '--norestore', '-env:UserInstallation=' . Ferramentas::urlArquivo($tmp . '/perfil'),
                '--convert-to', 'pdf', '--outdir', $tmp, $entrada,
            ]);
            if ($r['code'] === 127) {
                throw new UploadException('O LibreOffice não está instalado. No Windows: winget install --id TheDocumentFoundation.LibreOffice -e (ou defina PRESTACAO_SOFFICE). No Linux: sudo apt install libreoffice-impress');
            }
            if ($r['timeout']) {
                throw new UploadException('A conversão demorou demais e foi cancelada.');
            }
            $saida = $tmp . '/entrada.pdf';
            if (!is_file($saida) || filesize($saida) === 0 || $this->pdf->countPages($saida) === null) {
                throw new UploadException('Não foi possível converter o PowerPoint. O arquivo pode estar corrompido ou protegido por senha.');
            }
            return (string) file_get_contents($saida);
        } finally {
            $this->removeDir($tmp);
        }
    }

    /** Apresentação PowerPoint (OOXML): um ZIP que contém ppt/presentation.xml. */
    private static function ehPptx(string $path): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            return false;
        }
        $ok = $zip->locateName('ppt/presentation.xml') !== false;
        $zip->close();
        return $ok;
    }

    /** @return array{code:int,timeout:bool,out:string} */
    private function run(array $args): array
    {
        $proc = @proc_open(array_merge([$this->soffice], $args), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['HOME' => sys_get_temp_dir()] + getenv());
        if (!is_resource($proc)) {
            return ['code' => 127, 'timeout' => false, 'out' => ''];
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $out = '';
        $inicio = time();
        $timedOut = false;
        $code = -1;
        while (true) {
            $status = proc_get_status($proc);
            $out .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            if (!$status['running']) {
                $code = $status['exitcode'];
                break;
            }
            if (time() - $inicio > $this->timeout) {
                proc_terminate($proc, 9);
                $timedOut = true;
                break;
            }
            usleep(50000);
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        return ['code' => $timedOut ? 124 : $code, 'timeout' => $timedOut, 'out' => $out];
    }

    /** Remove somente pastas temporárias criadas por este serviço. */
    private function removeDir(string $dir): void
    {
        if (!str_starts_with(basename($dir), 'prestacao_lo_') || !is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() && !$f->isLink() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
