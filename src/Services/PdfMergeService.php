<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Junta PDFs com Ghostscript, sem shell (argumentos em array) e com timeout.
 *
 * ATENÇÃO: o Ghostscript retorna código 0 mesmo quando não consegue ler um arquivo
 * (ex.: PDF com senha: "No pages will be processed"), produzindo um PDF final sem
 * aquelas páginas. Por isso o resultado é validado pela contagem de páginas.
 */
final class PdfMergeService
{
    public function __construct(private ?string $gs = null, private int $timeout = 180)
    {
        $this->gs ??= Ferramentas::ghostscript();   // Linux: gs; Windows: gswin64c (specs/suporte-windows.md)
    }

    public function available(): bool
    {
        return $this->run(['--version'], 10)['code'] === 0;
    }

    /**
     * Número de páginas de um PDF, ou null se ele não puder ser lido
     * (corrompido, protegido por senha etc.).
     */
    public function countPages(string $path): ?int
    {
        $r = $this->run([
            '-q', '-dNODISPLAY', '-dBATCH', '-dNOPAUSE', '-dSAFER',
            '--permit-file-read=' . Ferramentas::caminhoGs($path), '-sPDFname=' . Ferramentas::caminhoGs($path),
            '-c', 'PDFname (r) file runpdfbegin pdfpagecount = quit',
        ], 60);
        if ($r['code'] !== 0 || str_contains($r['out'], '****')) {
            return null;
        }
        $linhas = array_values(array_filter(array_map('trim', explode("\n", $r['out'])), fn ($l) => $l !== ''));
        $ultima = end($linhas);
        if ($ultima === false || !ctype_digit($ultima) || (int) $ultima < 1) {
            return null;
        }
        return (int) $ultima;
    }

    /**
     * @param array<int,array{path:string,nome:string}> $arquivos na ordem final
     * @throws PdfMergeException com mensagem indicando o arquivo problemático
     */
    public function merge(array $arquivos, string $saida): void
    {
        if ($arquivos === []) {
            throw new PdfMergeException('Não há arquivos para juntar.');
        }
        if (!$this->available()) {
            throw new PdfMergeException('O Ghostscript não está instalado ou não pôde ser executado. No Windows: instale o "Ghostscript AGPL Release" de https://ghostscript.com/releases/gsdnld.html (ou defina PRESTACAO_GS). No Linux: sudo apt install ghostscript.');
        }

        // 1) Todo arquivo precisa ser legível; senão nada é gerado.
        $total = 0;
        $ruins = [];
        foreach ($arquivos as $a) {
            $n = $this->countPages($a['path']);
            $n === null ? $ruins[] = $a['nome'] : $total += $n;
        }
        if ($ruins !== []) {
            throw new PdfMergeException('Não foi possível ler: ' . implode(', ', $ruins)
                . '. O arquivo pode estar corrompido ou protegido por senha. Remova-o ou substitua por uma versão sem proteção.');
        }

        // 2) Junção.
        $r = $this->run(array_merge(
            ['-dBATCH', '-dNOPAUSE', '-dQUIET', '-dSAFER', '-sDEVICE=pdfwrite',
             '-dDownsampleColorImages=false', '-dDownsampleGrayImages=false', '-dDownsampleMonoImages=false',
             '-sOutputFile=' . Ferramentas::caminhoGs($saida)],
            array_map(Ferramentas::caminhoGs(...), array_column($arquivos, 'path'))
        ), $this->timeout);
        if ($r['code'] !== 0 || str_contains($r['out'], '****') || !is_file($saida) || filesize($saida) === 0) {
            @unlink($saida);
            throw new PdfMergeException('Falha ao juntar os PDFs.' . ($r['timeout'] ? ' Tempo limite excedido.' : ''));
        }

        // 3) Conferência: nenhuma página pode ter sido perdida.
        $gerado = $this->countPages($saida);
        if ($gerado !== $total) {
            @unlink($saida);
            throw new PdfMergeException("O PDF gerado não confere (esperado $total páginas, obtido " . ($gerado ?? 0) . '). Nada foi salvo.');
        }
    }

    /** @return array{code:int,timeout:bool,out:string} */
    private function run(array $args, int $timeout): array
    {
        $proc = @proc_open(array_merge([$this->gs], $args), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
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
            if (time() - $inicio > $timeout) {
                proc_terminate($proc, 9);
                $timedOut = true;
                break;
            }
            usleep(20000);
        }
        $out .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        return ['code' => $timedOut ? 124 : $code, 'timeout' => $timedOut, 'out' => $out];
    }
}
