<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\FileStorage;
use App\Services\ZipService;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/** ZIP da prestação de contas (specs/zip-prestacao.md). */
final class ZipTest extends TestCase
{
    private int $pid;
    /** @var list<string> */
    private array $temporarios = [];

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Caxambu do Sul', 'SC')");
        $mid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'Ônibus')");
        $this->pid = (int) $pdo->lastInsertId();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporarios as $t) {
            if (is_file($t)) {
                unlink($t);
            }
        }
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    /** Registra um "PDF" do documento com o conteúdo dado e devolve o caminho absoluto. */
    private function documento(string $codigo, string $conteudo, int $desatualizado = 0): string
    {
        $fs = FileStorage::default();
        $rel = $fs->storeGenerated($conteudo, 'projetos/' . $this->pid . '/teste');
        Database::connection()->prepare('INSERT INTO documentos (projeto_id, doc_codigo, caminho, nome_original, tamanho, desatualizado) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$this->pid, $codigo, $rel, "orig-$codigo.pdf", strlen($conteudo), $desatualizado]);
        return $fs->absolute($rel);
    }

    /** @return array<string,string> nome => conteúdo, na ordem do ZIP */
    private function lerZip(string $caminho): array
    {
        $z = new ZipArchive();
        $this->assertTrue($z->open($caminho) === true, 'ZIP válido');
        $out = [];
        for ($i = 0; $i < $z->numFiles; $i++) {
            $out[$z->getNameIndex($i)] = (string) $z->getFromIndex($i);
        }
        $z->close();
        return $out;
    }

    public function testZipHasExactlyTheExistingPdfsWithCatalogNamesInOrder(): void
    {
        // CA-Z01 (inclui o desatualizado, RN-Z02)
        $this->documento('22', '%PDF-fisico');
        $this->documento('01', '%PDF-balancete', 1);
        $this->documento('10a', '%PDF-art');
        Database::connection()->prepare("INSERT INTO arquivos (projeto_id, doc_codigo, ordem, nome_original, caminho, tamanho) VALUES (?, '03', 1, 'nf.pdf', 'x', 1)")
            ->execute([$this->pid]);   // "não gerado": não entra

        $res = $this->get("/projetos/{$this->pid}/zip");
        $this->assertSame(200, $res->status);
        $this->assertNotNull($res->file);
        $this->temporarios[] = $res->file;
        $this->assertTrue($res->apagarDepois);
        $this->assertSame('application/zip', $res->headers['Content-Type']);

        $this->assertSame([
            '01-balancete.pdf' => '%PDF-balancete',
            '10a-art-de-execucao.pdf' => '%PDF-art',
            '22-fisico-financeiro.pdf' => '%PDF-fisico',
        ], $this->lerZip($res->file));
    }

    public function testZipFileName(): void
    {
        // CA-Z02
        $this->documento('18', '%PDF');
        $res = $this->get("/projetos/{$this->pid}/zip");
        $this->temporarios[] = (string) $res->file;
        $this->assertStringContainsString("filename=\"prestacao-de-contas-caxambu-do-sul-projeto-{$this->pid}.zip\"", $res->headers['Content-Disposition']);
        $this->assertSame('sao-jose-dos-cedros', ZipService::slug('São José dos Cedros'));
        $this->assertSame('prestacao-de-contas-projeto-7.zip', ZipService::nomeArquivo(['id' => 7, 'municipio_nome' => '']));
    }

    public function testNoPdfRedirectsWithMessage(): void
    {
        // CA-Z04 / EX-05
        $res = $this->get("/projetos/{$this->pid}/zip");
        $this->assertSame(303, $res->status);
        $this->assertSame("/projetos/{$this->pid}", $res->headers['Location']);
        $this->assertStringContainsString('Não há documentos para baixar.', json_encode($_SESSION, JSON_UNESCAPED_UNICODE));
    }

    public function testMissingFileIsSkippedAndReported(): void
    {
        // EX-Z02
        $this->documento('18', '%PDF-parecer');
        unlink($this->documento('19', '%PDF-cnd'));
        $res = $this->get("/projetos/{$this->pid}/zip");
        $this->temporarios[] = (string) $res->file;
        $this->assertSame(['18-parecer-do-controle-interno.pdf'], array_keys($this->lerZip((string) $res->file)));
        $this->assertStringContainsString('O documento 19 não foi incluído no ZIP: arquivo não encontrado.', json_encode($_SESSION, JSON_UNESCAPED_UNICODE));
    }

    public function testTempFileIsDeletedAfterSending(): void
    {
        // CA-Z05
        $tmp = tempnam(sys_get_temp_dir(), 'prestacao-zip-');
        file_put_contents($tmp, 'conteudo');
        $res = Response::tempFile($tmp, 'x.zip', 'application/zip');
        ob_start();
        @$res->send();   // em CLI, header() só emite aviso
        $saida = ob_get_clean();
        $this->assertSame('conteudo', $saida);
        $this->assertFileDoesNotExist($tmp);

        // download normal não apaga
        $outro = tempnam(sys_get_temp_dir(), 'prestacao-zip-');
        $this->temporarios[] = $outro;
        ob_start();
        @Response::file($outro, 'y.pdf', 'application/pdf')->send();
        ob_end_clean();
        $this->assertFileExists($outro);
    }

    public function testUnknownProjectIs404(): void
    {
        $this->assertSame(404, $this->get('/projetos/999999/zip')->status);
    }

    public function testProjectPageButtonAndWarnings(): void
    {
        // CA-Z03 e RF-Z05
        $page = $this->get("/projetos/{$this->pid}")->body;
        $this->assertStringContainsString('<span class="btn" aria-disabled="true" title="Nenhum documento com PDF ainda">Baixar prestação (ZIP)</span>', $page);
        $this->assertStringNotContainsString("/projetos/{$this->pid}/zip", $page);

        $this->documento('18', '%PDF');
        $page = $this->get("/projetos/{$this->pid}")->body;
        $this->assertStringContainsString("<a class=\"btn\" href=\"/projetos/{$this->pid}/zip\">Baixar prestação (ZIP)</a>", $page, 'sem pendências: sem confirmação');
        $this->assertStringContainsString('1 documento(s) no ZIP', $page);

        $this->documento('01', '%PDF', 1);
        Database::connection()->prepare("INSERT INTO arquivos (projeto_id, doc_codigo, ordem, nome_original, caminho, tamanho) VALUES (?, '03', 1, 'nf.pdf', 'x', 1)")
            ->execute([$this->pid]);
        $page = $this->get("/projetos/{$this->pid}")->body;
        $this->assertStringContainsString(
            'onclick="return confirm(&quot;1 documento(s) desatualizado(s): 01.\n1 documento(s) não gerado(s): 03 (não entram no ZIP).\n\nBaixar mesmo assim?&quot;)"',
            $page
        );
        $this->assertStringContainsString('<span class="tag aviso-tag">1 desatualizado(s)</span> (01)', $page);
        $this->assertStringContainsString('<span class="tag aviso-tag">1 não gerado(s)</span> (03)', $page);
    }
}
