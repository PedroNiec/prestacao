<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Guarda arquivos em storage/ com nomes gerados (nunca o nome do usuário).
 * Caminhos retornados/aceitos são relativos a storage/.
 */
final class FileStorage
{
    public const MAX_BYTES = 50 * 1024 * 1024;

    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
        UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido.',
        UPLOAD_ERR_PARTIAL => 'O envio do arquivo foi interrompido. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Selecione um arquivo para enviar.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária de upload indisponível no servidor.',
        UPLOAD_ERR_CANT_WRITE => 'Não foi possível gravar o arquivo no disco.',
        UPLOAD_ERR_EXTENSION => 'O envio foi bloqueado por uma extensão do PHP.',
    ];

    /** MIME real aceito para cada extensão permitida. */
    private const MIMES = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    ];

    public function __construct(private string $root, private bool $httpUploads = true)
    {
        $this->root = rtrim($root, '/');
    }

    public static function default(): self
    {
        return new self(getenv('PRESTACAO_STORAGE') ?: dirname(__DIR__, 2) . '/storage', PHP_SAPI !== 'cli');
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * Valida e grava um arquivo recebido do formulário.
     *
     * @param array{name?:string,tmp_name?:string,error?:int,size?:int} $file entrada de $_FILES
     * @param string[] $extensoes extensões permitidas (minúsculas, sem ponto)
     * @return array{caminho:string,nome_original:string,tamanho:int}
     */
    public function storeUpload(array $file, string $subdir, array $extensoes): array
    {
        $erro = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erro !== UPLOAD_ERR_OK) {
            throw new UploadException(self::UPLOAD_ERRORS[$erro] ?? 'Falha no envio do arquivo.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp) || ($this->httpUploads && !is_uploaded_file($tmp))) {
            throw new UploadException('Arquivo de upload inválido.');
        }

        $nome = $this->cleanName((string) ($file['name'] ?? ''));
        $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensoes, true)) {
            throw new UploadException('Formato não permitido. Envie um arquivo ' . strtoupper(implode(' ou ', $extensoes)) . '.');
        }

        $tamanho = (int) filesize($tmp);
        if ($tamanho === 0) {
            throw new UploadException('O arquivo está vazio.');
        }
        if ($tamanho > self::MAX_BYTES) {
            throw new UploadException('O arquivo excede o limite de ' . (self::MAX_BYTES / 1024 / 1024) . ' MB.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!in_array($mime, self::MIMES[$ext] ?? [], true)) {
            throw new UploadException('O conteúdo do arquivo não corresponde ao formato ' . strtoupper($ext) . '.');
        }
        if ($ext === 'pdf' && file_get_contents($tmp, false, null, 0, 5) !== '%PDF-') {
            throw new UploadException('O arquivo não é um PDF válido.');
        }

        $relativo = $this->newPath($subdir, $ext);
        $destino = $this->absolute($relativo);
        $moved = $this->httpUploads ? move_uploaded_file($tmp, $destino) : copy($tmp, $destino);
        if (!$moved) {
            throw new UploadException('Não foi possível gravar o arquivo.');
        }
        chmod($destino, 0640);

        return ['caminho' => $relativo, 'nome_original' => $nome, 'tamanho' => $tamanho];
    }

    /** Grava conteúdo gerado pelo sistema (PDF montado etc.) e devolve o caminho relativo. */
    public function storeGenerated(string $conteudo, string $subdir, string $ext = 'pdf'): string
    {
        $relativo = $this->newPath($subdir, $ext);
        if (file_put_contents($this->absolute($relativo), $conteudo) === false) {
            throw new UploadException('Não foi possível gravar o arquivo gerado.');
        }
        return $relativo;
    }

    /** Caminho absoluto de um arquivo registrado; falha se escapar de storage/. */
    public function absolute(string $relativo): string
    {
        if ($relativo === '' || str_contains($relativo, '..') || str_contains($relativo, "\0") || $relativo[0] === '/') {
            throw new \InvalidArgumentException('Caminho inválido.');
        }
        return $this->root . '/' . $relativo;
    }

    public function exists(string $relativo): bool
    {
        return is_file($this->absolute($relativo));
    }

    public function delete(string $relativo): void
    {
        $path = $this->absolute($relativo);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /** Remove a pasta de um projeto inteira (ex.: ao excluir o projeto). */
    public function deleteDir(string $subdir): void
    {
        $dir = $this->absolute($subdir);
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() && !$f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }

    /** Garante que a pasta (relativa a storage/) exista. */
    public function prepareDir(string $subdir): void
    {
        $dir = $this->absolute($subdir);
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new UploadException('Não foi possível criar a pasta de armazenamento.');
        }
    }

    private function newPath(string $subdir, string $ext): string
    {
        $dir = $this->absolute($subdir);
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new UploadException('Não foi possível criar a pasta de armazenamento.');
        }
        return $subdir . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
    }

    private function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        return mb_substr($name, 0, 200);
    }
}
