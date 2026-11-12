<?php
declare(strict_types=1);

namespace Tests;

/** Utilitários de teste para arquivos de upload. */
final class Support
{
    /** PDF mínimo e válido para finfo (application/pdf). */
    public const PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R/Size 4>>\n%%EOF\n";

    /** Cria um arquivo temporário e devolve uma entrada no formato de $_FILES. */
    public static function file(string $conteudo, string $nome = 'doc.pdf'): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, $conteudo);
        return ['name' => $nome, 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($conteudo)];
    }

    public static function pdf(string $nome = 'doc.pdf'): array
    {
        return self::file(self::PDF, $nome);
    }

    /** Gera um PDF real (via Ghostscript) em que cada página contém o texto "$rotulo-N". */
    public static function realPdf(string $rotulo, int $paginas = 1, ?string $senha = null): string
    {
        $out = tempnam(sys_get_temp_dir(), 'pdf');
        $ps = '/Helvetica findfont 24 scalefont setfont ';
        for ($i = 1; $i <= $paginas; $i++) {
            $ps .= "50 700 moveto ($rotulo-$i) show showpage ";
        }
        $args = ['gs', '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER', '-sDEVICE=pdfwrite', '-sOutputFile=' . $out];
        if ($senha !== null) {
            array_push($args, '-sOwnerPassword=' . $senha, '-sUserPassword=' . $senha, '-dEncryptionR=3', '-dKeyLength=128');
        }
        array_push($args, '-c', $ps);
        $cmd = implode(' ', array_map('escapeshellarg', $args));
        exec($cmd . ' 2>&1', $o, $code);
        if ($code !== 0) {
            throw new \RuntimeException('Falha ao gerar PDF de teste: ' . implode("\n", $o));
        }
        return $out;
    }

    /** Entrada de $_FILES a partir de um PDF existente em disco. */
    public static function fromPath(string $path, string $nome): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        copy($path, $tmp);
        return ['name' => $nome, 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
    }

    /**
     * Texto extraído de um PDF (para conferir conteúdo e ordem das páginas).
     * Grava em arquivo: pela saída padrão o Ghostscript às vezes intercala uma mensagem de erro no texto.
     */
    public static function texto(string $pdf): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'txt');
        exec(implode(' ', array_map('escapeshellarg', ['gs', '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER', '-sDEVICE=txtwrite', '-sOutputFile=' . $tmp, $pdf])) . ' >/dev/null 2>&1');
        $texto = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $texto;
    }

    /** Texto de uma única página (1-indexada). */
    public static function textoPagina(string $pdf, int $pagina): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'txt');
        exec(implode(' ', array_map('escapeshellarg', ['gs', '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER', '-sDEVICE=txtwrite',
            '-dFirstPage=' . $pagina, '-dLastPage=' . $pagina, '-sOutputFile=' . $tmp, $pdf])) . ' >/dev/null 2>&1');
        $texto = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $texto;
    }

    public static function paginas(string $pdf): int
    {
        return preg_match_all('/^\s*(\S+-\d+)\s*$/m', self::texto($pdf));
    }

    /** Cria um PNG real via GD e devolve o caminho. */
    public static function png(int $w, int $h, bool $transparente = false): string
    {
        $img = imagecreatetruecolor($w, $h);
        if ($transparente) {
            imagealphablending($img, false);
            imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
            imagesavealpha($img, true);
        } else {
            imagefill($img, 0, 0, imagecolorallocate($img, 30, 90, 160));
            imagefilledrectangle($img, 10, 10, $w - 10, (int) ($h / 2), imagecolorallocate($img, 240, 200, 40));
        }
        $out = tempnam(sys_get_temp_dir(), 'png');
        imagepng($img, $out);
        return $out;
    }

    /** PPTX real de 2 slides ("SLIDE-UM", "SLIDE-DOIS"), gerado pelo LibreOffice e reutilizado. */
    public static function pptx(): string
    {
        static $cache = null;
        if ($cache !== null && is_file($cache)) {
            return $cache;
        }
        $dir = sys_get_temp_dir() . '/pptx_fixture_' . bin2hex(random_bytes(4));
        mkdir($dir . '/perfil', 0700, true);
        $slide = fn ($t) => '<draw:page draw:name="' . $t . '"><draw:frame svg:width="20cm" svg:height="3cm" svg:x="2cm" svg:y="2cm"><draw:text-box><text:p>' . $t . '</text:p></draw:text-box></draw:frame></draw:page>';
        file_put_contents($dir . '/deck.fodp', '<?xml version="1.0" encoding="UTF-8"?><office:document xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" office:version="1.2" office:mimetype="application/vnd.oasis.opendocument.presentation"><office:body><office:presentation>' . $slide('SLIDE-UM') . $slide('SLIDE-DOIS') . '</office:presentation></office:body></office:document>');
        exec(implode(' ', array_map('escapeshellarg', ['soffice', '--headless', '-env:UserInstallation=file://' . $dir . '/perfil', '--convert-to', 'pptx', '--outdir', $dir, $dir . '/deck.fodp'])) . ' 2>&1', $o, $code);
        if (!is_file($dir . '/deck.pptx')) {
            throw new \RuntimeException('Falha ao criar PPTX de teste: ' . implode("\n", $o));
        }
        return $cache = $dir . '/deck.pptx';
    }

    public static function soffice(): bool
    {
        exec('soffice --version 2>&1', $o, $code);
        return $code === 0;
    }

    /** Remove pastas temporárias de conversão deixadas para trás. */
    public static function sobrasDeConversao(): array
    {
        return glob(sys_get_temp_dir() . '/prestacao_lo_*') ?: [];
    }
}
