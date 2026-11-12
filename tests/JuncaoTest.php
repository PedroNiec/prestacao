<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FileStorage;
use PHPUnit\Framework\TestCase;

final class JuncaoTest extends TestCase
{
    private int $pid;
    private array $tmp = [];

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('M', 'SC')");
        $mid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'Obj')");
        $this->pid = (int) $pdo->lastInsertId();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $f) {
            @unlink($f);
        }
    }

    private function pdf(string $rotulo, int $paginas = 1, ?string $senha = null): string
    {
        return $this->tmp[] = Support::realPdf($rotulo, $paginas, $senha);
    }

    private function post(string $path, array $post = [], array $files = []): Response
    {
        return handle(new Request('POST', $path, [], $post + ['_csrf' => Csrf::token()], $files), 'localhost');
    }

    private function get(string $path, array $q = []): Response
    {
        return handle(new Request('GET', $path, $q), 'localhost');
    }

    /** Envia vários arquivos no formato de campo múltiplo ($_FILES['arquivos']). */
    private function enviar(string $codigo, array $entradas): Response
    {
        $m = ['name' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
        foreach ($entradas as $e) {
            foreach ($m as $k => $_) {
                $m[$k][] = $e[$k];
            }
        }
        return $this->post("/projetos/{$this->pid}/documentos/$codigo/arquivos", [], ['arquivos' => $m]);
    }

    private function ids(string $codigo): array
    {
        $st = Database::connection()->prepare('SELECT id FROM arquivos WHERE projeto_id = ? AND doc_codigo = ? ORDER BY ordem');
        $st->execute([$this->pid, $codigo]);
        return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
    }

    private function nomes(string $codigo): array
    {
        $st = Database::connection()->prepare('SELECT nome_original FROM arquivos WHERE projeto_id = ? AND doc_codigo = ? ORDER BY ordem');
        $st->execute([$this->pid, $codigo]);
        return $st->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function final(string $codigo): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM documentos WHERE projeto_id = ? AND doc_codigo = ?');
        $st->execute([$this->pid, $codigo]);
        return $st->fetch() ?: null;
    }

    private function tresArquivos(string $codigo = '03'): void
    {
        $this->enviar($codigo, [
            Support::fromPath($this->pdf('AAA', 2), 'a.pdf'),
            Support::fromPath($this->pdf('BBB', 1), 'b.pdf'),
            Support::fromPath($this->pdf('CCC', 3), 'c.pdf'),
        ]);
    }

    public function testMultipleFilesAreAddedInOrder(): void
    {
        $this->tresArquivos();
        $this->assertSame(['a.pdf', 'b.pdf', 'c.pdf'], $this->nomes('03'));
    }

    public function testMergeProducesSinglePdfInOrder(): void
    {
        $this->tresArquivos();
        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $final = $this->final('03');
        $this->assertNotNull($final, 'PDF final deveria existir');
        $path = FileStorage::default()->absolute($final['caminho']);
        $this->assertSame(6, Support::paginas($path));
        $texto = Support::texto($path);
        $this->assertLessThan(strpos($texto, 'BBB-1'), strpos($texto, 'AAA-2'));
        $this->assertLessThan(strpos($texto, 'CCC-1'), strpos($texto, 'BBB-1'));
    }

    public function testReorderChangesMergedOrder(): void
    {
        $this->tresArquivos();
        [$a, $b, $c] = $this->ids('03');
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/ordem", ['ordem' => [$c, $a, $b]]);
        $this->assertSame(['c.pdf', 'a.pdf', 'b.pdf'], $this->nomes('03'));

        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $texto = Support::texto(FileStorage::default()->absolute($this->final('03')['caminho']));
        $this->assertLessThan(strpos($texto, 'AAA-1'), strpos($texto, 'CCC-1'));
        $this->assertLessThan(strpos($texto, 'BBB-1'), strpos($texto, 'AAA-2'));
    }

    public function testMoveUpAndDown(): void
    {
        $this->tresArquivos();
        [$a, $b, $c] = $this->ids('03');
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/$c/mover", ['direcao' => 'cima']);
        $this->assertSame(['a.pdf', 'c.pdf', 'b.pdf'], $this->nomes('03'));
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/$a/mover", ['direcao' => 'cima']); // já é o primeiro
        $this->assertSame(['a.pdf', 'c.pdf', 'b.pdf'], $this->nomes('03'));
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/$a/mover", ['direcao' => 'baixo']);
        $this->assertSame(['c.pdf', 'a.pdf', 'b.pdf'], $this->nomes('03'));
    }

    public function testReorderWithForeignIdsIsRejected(): void
    {
        $this->tresArquivos();
        [$a, $b, $c] = $this->ids('03');
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/ordem", ['ordem' => [$a, $b, 99999]]);
        $this->assertSame(['a.pdf', 'b.pdf', 'c.pdf'], $this->nomes('03'));
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/ordem", ['ordem' => [$a, $b]]);
        $this->assertSame(['a.pdf', 'b.pdf', 'c.pdf'], $this->nomes('03'));
    }

    public function testRemoveFileDeletesFromDisk(): void
    {
        $this->tresArquivos();
        $id = $this->ids('03')[1];
        $caminho = Database::connection()->query("SELECT caminho FROM arquivos WHERE id = $id")->fetchColumn();
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/$id/remover");
        $this->assertSame(['a.pdf', 'c.pdf'], $this->nomes('03'));
        $this->assertFileDoesNotExist(FileStorage::default()->absolute($caminho));
    }

    public function testChangingListMarksFinalPdfOutdated(): void
    {
        $this->tresArquivos();
        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $this->assertSame(0, (int) $this->final('03')['desatualizado']);

        $this->enviar('03', [Support::fromPath($this->pdf('DDD'), 'd.pdf')]);
        $this->assertSame(1, (int) $this->final('03')['desatualizado']);
        $this->assertStringContainsString('Desatualizado', $this->get("/projetos/{$this->pid}")->body);

        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $this->assertSame(0, (int) $this->final('03')['desatualizado']);
        $this->assertSame(1, (int) Database::connection()->query("SELECT COUNT(*) FROM documentos WHERE doc_codigo='03'")->fetchColumn());
    }

    public function testRegeneratingReplacesPreviousPdfFile(): void
    {
        $this->tresArquivos();
        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $antigo = $this->final('03')['caminho'];
        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $novo = $this->final('03')['caminho'];
        $this->assertNotSame($antigo, $novo);
        $this->assertFileDoesNotExist(FileStorage::default()->absolute($antigo));
    }

    public function testGenerateWithoutFilesShowsError(): void
    {
        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $this->assertNull($this->final('03'));
        $flash = Session::pullFlash();
        $this->assertSame('erro', end($flash)['tipo']);
    }

    public function testPasswordProtectedPdfIsIdentifiedByName(): void
    {
        $this->enviar('04', [
            Support::fromPath($this->pdf('OK'), 'bom.pdf'),
            Support::fromPath($this->pdf('SECRETO', 1, 'senha123'), 'protegido.pdf'),
        ]);
        Session::pullFlash();
        $this->post("/projetos/{$this->pid}/documentos/04/gerar");
        $this->assertNull($this->final('04'));
        $msg = implode(' ', array_column(Session::pullFlash(), 'mensagem'));
        $this->assertStringContainsString('protegido.pdf', $msg);
        $this->assertStringNotContainsString('bom.pdf', $msg);
    }

    public function testCorruptedPdfIsReportedByNameAndNothingIsSaved(): void
    {
        $lixo = Support::file("%PDF-1.4\nisso nao e um pdf de verdade \x00\x01\x02", 'quebrado.pdf');
        $this->enviar('05', [Support::fromPath($this->pdf('OK'), 'bom.pdf'), $lixo]);
        Session::pullFlash();
        $this->post("/projetos/{$this->pid}/documentos/05/gerar");
        $this->assertNull($this->final('05'));
        $msg = implode(' ', array_column(Session::pullFlash(), 'mensagem'));
        $this->assertStringContainsString('quebrado.pdf', $msg);
        $this->assertStringNotContainsString('bom.pdf', $msg);
    }

    public function testFailedGenerationKeepsPreviousPdf(): void
    {
        $this->enviar('04', [Support::fromPath($this->pdf('OK', 2), 'bom.pdf')]);
        $this->post("/projetos/{$this->pid}/documentos/04/gerar");
        $antigo = $this->final('04')['caminho'];

        $this->enviar('04', [Support::fromPath($this->pdf('SECRETO', 1, 'x'), 'protegido.pdf')]);
        $this->post("/projetos/{$this->pid}/documentos/04/gerar");
        $this->assertSame($antigo, $this->final('04')['caminho']);
        $this->assertFileExists(FileStorage::default()->absolute($antigo));
        $this->assertSame(1, (int) $this->final('04')['desatualizado']);
    }

    public function testInvalidFileInBatchDoesNotBlockValidOnes(): void
    {
        $res = $this->enviar('06', [
            Support::fromPath($this->pdf('X'), 'ok.pdf'),
            Support::file('texto', 'falso.pdf'),
            Support::file(Support::PDF, 'malware.exe'),
        ]);
        $this->assertSame(['ok.pdf'], $this->nomes('06'));
        $msgs = Session::pullFlash();
        $erros = array_filter($msgs, fn ($m) => $m['tipo'] === 'erro');
        $this->assertCount(2, $erros);
        $txt = implode(' ', array_column($erros, 'mensagem'));
        $this->assertStringContainsString('falso.pdf', $txt);
        $this->assertStringContainsString('malware.exe', $txt);
    }

    public function testNonMergeDocumentRoutesAre404(): void
    {
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/documentos/18")->status);
        $this->assertSame(404, $this->post("/projetos/{$this->pid}/documentos/18/gerar")->status);
        $this->assertSame(404, $this->get("/projetos/9999/documentos/03")->status);
    }

    public function testCannotViewOrRemoveFileOfAnotherDocument(): void
    {
        $this->enviar('02', [Support::fromPath($this->pdf('X'), 'x.pdf')]);
        $id = $this->ids('02')[0];
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/documentos/03/arquivos/$id/ver")->status);
        $this->post("/projetos/{$this->pid}/documentos/03/arquivos/$id/remover");
        $this->assertSame(['x.pdf'], $this->nomes('02'));
    }

    public function testManagePageRendersAndViewServesPdf(): void
    {
        $this->tresArquivos();
        $page = $this->get("/projetos/{$this->pid}/documentos/03");
        $this->assertSame(200, $page->status);
        $this->assertSame(3, substr_count($page->body, 'draggable="true"'));
        $id = $this->ids('03')[0];
        $v = $this->get("/projetos/{$this->pid}/documentos/03/arquivos/$id/ver");
        $this->assertSame('application/pdf', $v->headers['Content-Type']);
        $this->assertStringStartsWith('inline', $v->headers['Content-Disposition']);
    }

    public function testDownloadMergedPdfAfterGenerate(): void
    {
        $this->tresArquivos();
        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $res = $this->get("/projetos/{$this->pid}/documentos/03/baixar");
        $this->assertSame(200, $res->status);
        $this->assertStringContainsString('03-notas-fiscais.pdf', $res->headers['Content-Disposition']);
    }

    public function testDeletingProjectRemovesSourceAndMergedFiles(): void
    {
        $this->tresArquivos();
        $this->post("/projetos/{$this->pid}/documentos/03/gerar");
        $this->post("/projetos/{$this->pid}/excluir");
        $this->assertSame(0, (int) Database::connection()->query('SELECT COUNT(*) FROM arquivos')->fetchColumn());
        $this->assertDirectoryDoesNotExist(FileStorage::default()->absolute('projetos/' . $this->pid));
    }

    public function testMergeFilesAreInSeparatePerProjectFolder(): void
    {
        $this->tresArquivos();
        $c = Database::connection()->query('SELECT caminho FROM arquivos LIMIT 1')->fetchColumn();
        $this->assertStringStartsWith('projetos/' . $this->pid . '/juncao/', $c);
    }
}
