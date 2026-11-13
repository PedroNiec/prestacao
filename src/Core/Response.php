<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        public readonly string $body = '',
        public readonly int $status = 200,
        public readonly array $headers = [],
        public readonly ?string $file = null,
        public readonly bool $apagarDepois = false,
    ) {
    }

    public static function redirect(string $to, int $status = 303): self
    {
        return new self('', $status, ['Location' => $to]);
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /** Resposta que envia um arquivo do disco (download/inline). */
    public static function file(string $path, string $downloadName, string $mime, bool $inline = false): self
    {
        $disp = $inline ? 'inline' : 'attachment';
        $safe = str_replace(['"', "\r", "\n", '\\'], '_', $downloadName);
        return new self('', 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) filesize($path),
            'Content-Disposition' => $disp . '; filename="' . $safe . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName),
            'X-Content-Type-Options' => 'nosniff',
        ], $path);
    }

    /** Como file(), mas o arquivo (temporário) é apagado depois do envio. Usado pelo ZIP da prestação. */
    public static function tempFile(string $path, string $downloadName, string $mime): self
    {
        $base = self::file($path, $downloadName, $mime);
        return new self($base->body, $base->status, $base->headers, $path, true);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        if ($this->file !== null) {
            readfile($this->file);
            if ($this->apagarDepois && is_file($this->file)) {
                @unlink($this->file);
            }
            return;
        }
        echo $this->body;
    }
}
