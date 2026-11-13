<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Localiza as ferramentas externas (Ghostscript e LibreOffice) no Linux e no Windows (specs/suporte-windows.md).
 *
 * Ordem: variável de ambiente → (só Windows) instalação padrão do winget → nome curto (gs / soffice), que é o
 * comportamento original no Linux. Sistema, ambiente e busca de arquivos são injetáveis para os testes.
 */
final class Ferramentas
{
    /**
     * @param ?callable(string):?string $noPath   devolve o caminho de um executável encontrado no PATH, ou null
     * @param ?callable(string):array    $procurar glob de arquivos
     */
    public static function ghostscript(?string $os = null, ?array $env = null, ?callable $noPath = null, ?callable $procurar = null): string
    {
        $env ??= getenv();
        if (($env['PRESTACAO_GS'] ?? '') !== '') {
            return $env['PRESTACAO_GS'];
        }
        if (self::windows($os)) {
            $noPath ??= self::noPath(...);
            $procurar ??= fn (string $p) => glob($p) ?: [];
            if (($p = $noPath('gswin64c.exe')) !== null) {
                return $p;
            }
            $instalados = $procurar(self::programFiles($env) . '\\gs\\gs*\\bin\\gswin64c.exe');
            if ($instalados !== []) {
                natsort($instalados);                 // gs9.56 < gs10.04: a versão mais recente fica por último
                return (string) end($instalados);
            }
        }
        return 'gs';
    }

    /** @param ?callable(string):bool $existe */
    public static function libreOffice(?string $os = null, ?array $env = null, ?callable $existe = null): string
    {
        $env ??= getenv();
        if (($env['PRESTACAO_SOFFICE'] ?? '') !== '') {
            return $env['PRESTACAO_SOFFICE'];
        }
        if (self::windows($os)) {
            $existe ??= is_file(...);
            $padrao = self::programFiles($env) . '\\LibreOffice\\program\\soffice.exe';
            if ($existe($padrao)) {
                return $padrao;
            }
        }
        return 'soffice';
    }

    /** URL de arquivo válida nos dois sistemas: C:\a b\c → file:///C:/a%20b/c ; /tmp/x → file:///tmp/x */
    public static function urlArquivo(string $caminho): string
    {
        $c = str_replace('\\', '/', $caminho);
        $partes = array_map(
            fn (string $p) => preg_match('/^[A-Za-z]:$/', $p) ? $p : rawurlencode($p),
            explode('/', ltrim($c, '/'))
        );
        return 'file:///' . implode('/', $partes);
    }

    /** Caminho no formato aceito pelo Ghostscript nos dois sistemas (barras normais). */
    public static function caminhoGs(string $caminho): string
    {
        return str_replace('\\', '/', $caminho);
    }

    private static function windows(?string $os): bool
    {
        return ($os ?? PHP_OS_FAMILY) === 'Windows';
    }

    private static function programFiles(array $env): string
    {
        return rtrim((string) ($env['ProgramFiles'] ?? $env['PROGRAMFILES'] ?? 'C:\\Program Files'), '\\');
    }

    private static function noPath(string $exe): ?string
    {
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
            if ($dir !== '' && is_file($dir . DIRECTORY_SEPARATOR . $exe)) {
                return $dir . DIRECTORY_SEPARATOR . $exe;
            }
        }
        return null;
    }
}
