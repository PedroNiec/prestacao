<?php
declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/** Converte o HTML de um relatório em PDF (A4). */
final class ReportPdfService
{
    /** @throws \RuntimeException */
    public function render(string $html, string $orientation = 'landscape'): string
    {
        $limite = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        try {
            $opt = new Options();
            $opt->set('isRemoteEnabled', false);
            $opt->set('isPhpEnabled', false);
            $opt->set('defaultFont', 'Helvetica');
            $dompdf = new Dompdf($opt);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', $orientation);
            $dompdf->render();
            return $dompdf->output();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Não foi possível gerar o PDF do relatório.', 0, $e);
        } finally {
            ini_set('memory_limit', (string) $limite);
        }
    }
}
