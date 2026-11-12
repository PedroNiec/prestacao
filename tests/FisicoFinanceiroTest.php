<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\FisicoFinanceiroCalculator;
use App\Domain\Money;
use App\Domain\Validator;
use App\Services\FileStorage;
use App\Services\PdfMergeService;
use PHPUnit\Framework\TestCase;

final class FisicoFinanceiroTest extends TestCase
{
    private int $pid;
    private int $outro;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Caxambu do Sul', 'SC')");
        $mid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto, local, data_assinatura, prefeito_nome, contador_nome, contador_crc)
                    VALUES ($mid, 'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', 'Caxambu do Sul - SC', '2026-03-05', 'EDI MARCOS', 'MAICON LUFT', 'CRC/SC 102.316')");
        $this->pid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'Outro')");
        $this->outro = (int) $pdo->lastInsertId();
    }

    private function post(string $path, array $d = []): Response
    {
        return handle(new Request('POST', $path, [], $d + ['_csrf' => Csrf::token()]), 'localhost');
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    private function linha(array $o = [], ?int $pid = null): Response
    {
        return $this->post('/projetos/' . ($pid ?? $this->pid) . '/fisico-financeiro/linhas', $o + [
            'meta' => '1', 'etapa' => '1', 'descricao' => 'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', 'unidade' => 'Unid',
            'fis_prog_periodo' => '1', 'fis_exec_periodo' => '1', 'fis_prog_ate' => '1', 'fis_exec_ate' => '1',
            'fin_conc_periodo' => '497.152,49', 'fin_conc_ate' => '497.152,49',
        ]);
    }

    private function rows(?int $pid = null): array
    {
        $st = Database::connection()->prepare('SELECT * FROM fisico_financeiro WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$pid ?? $this->pid]);
        return $st->fetchAll();
    }

    private function pdfPath(): string
    {
        $st = Database::connection()->prepare("SELECT caminho FROM documentos WHERE projeto_id = ? AND doc_codigo = '22'");
        $st->execute([$this->pid]);
        return FileStorage::default()->absolute((string) $st->fetchColumn());
    }

    // ---- Domínio ----

    public function testRowTotalsFollowSpreadsheetFormulas(): void
    {
        $t = FisicoFinanceiroCalculator::totaisLinha([
            'fin_conc_periodo' => 1000, 'fin_exec_periodo' => 200, 'fin_rend_periodo' => 30, 'fin_conc_ate' => 700, 'fin_exec_ate' => 5,
        ]);
        $this->assertSame(1230, $t['total_periodo']);   // concedente + executor + outros rendimentos
        $this->assertSame(705, $t['total_ate']);        // concedente + executor
    }

    public function testTotalsSumAllRowsAndKeepSameUnit(): void
    {
        $base = ['fin_conc_periodo' => 100, 'fin_exec_periodo' => 0, 'fin_rend_periodo' => 0, 'fin_conc_ate' => 100, 'fin_exec_ate' => 0,
            'fis_prog_periodo' => 150, 'fis_exec_periodo' => 100, 'fis_prog_ate' => 150, 'fis_exec_ate' => 100];
        $t = FisicoFinanceiroCalculator::totais([$base + ['unidade' => 'm²'], $base + ['unidade' => 'M²']]);
        $this->assertNotNull($t['fisico']);
        $this->assertSame(300, $t['fisico']['fis_prog_periodo']);
        $this->assertSame('m²', $t['fisico']['unidade']);
        $this->assertSame(200, $t['financeiro']['total_periodo']);
    }

    public function testPhysicalTotalIsSuppressedForDifferentUnits(): void
    {
        $base = ['fin_conc_periodo' => 0, 'fin_exec_periodo' => 0, 'fin_rend_periodo' => 0, 'fin_conc_ate' => 0, 'fin_exec_ate' => 0,
            'fis_prog_periodo' => 100, 'fis_exec_periodo' => 100, 'fis_prog_ate' => 100, 'fis_exec_ate' => 100];
        $t = FisicoFinanceiroCalculator::totais([$base + ['unidade' => 'm²'], $base + ['unidade' => 'Unid']]);
        $this->assertNull($t['fisico']);
    }

    public function testQuantityFormat(): void
    {
        $this->assertSame('1', Money::formatQuantidade(100));
        $this->assertSame('0', Money::formatQuantidade(0));
        $this->assertSame('1.234,50', Money::formatQuantidade(123450));
        $this->assertSame('0,25', Money::formatQuantidade(25));
    }

    public function testValidatorBlankNumbersAreZeroAndInvalidAreErrors(): void
    {
        $ok = Validator::fisicoFinanceiro(['descricao' => 'X', 'fis_prog_periodo' => '', 'fin_conc_periodo' => '1.500,50']);
        $this->assertSame([], $ok['erros']);
        $this->assertSame(0, $ok['dados']['fis_prog_periodo']);
        $this->assertSame(150050, $ok['dados']['fin_conc_periodo']);

        $ruim = Validator::fisicoFinanceiro(['descricao' => '', 'fis_exec_ate' => 'abc', 'fin_exec_ate' => '-5,00']);
        $this->assertCount(3, $ruim['erros']);
    }

    // ---- Fluxo ----

    public function testCreateStoresValuesAndPageShowsTotals(): void
    {
        $this->linha();
        $r = $this->rows()[0];
        $this->assertSame(100, (int) $r['fis_exec_ate']);
        $this->assertSame(49715249, (int) $r['fin_conc_periodo']);
        $body = $this->get("/projetos/{$this->pid}/fisico-financeiro")->body;
        $this->assertStringContainsString('497.152,49', $body);
        $this->assertStringContainsString('AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', $body);
    }

    public function testNewFormSuggestsObjetoAsDescription(): void
    {
        $body = $this->get("/projetos/{$this->pid}/fisico-financeiro/linhas/novo")->body;
        $this->assertStringContainsString('AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', $body);
    }

    public function testInvalidInputIsNotSavedAndMessagesAreShown(): void
    {
        $res = $this->linha(['fin_conc_periodo' => 'abc', 'descricao' => '']);
        $this->assertSame("/projetos/{$this->pid}/fisico-financeiro/linhas/novo", $res->headers['Location']);
        $this->assertCount(0, $this->rows());
    }

    public function testEditShowsValuesInBrazilianFormatAndUpdates(): void
    {
        $this->linha(['fin_conc_periodo' => '1.234,50', 'fis_prog_periodo' => '2,5']);
        $id = (int) $this->rows()[0]['id'];
        $form = $this->get("/projetos/{$this->pid}/fisico-financeiro/linhas/$id/editar")->body;
        $this->assertStringContainsString('value="1234,50"', $form);
        $this->assertStringContainsString('value="2,50"', $form);
        $this->post("/projetos/{$this->pid}/fisico-financeiro/linhas/$id", ['descricao' => 'Nova', 'fin_conc_periodo' => '10,00']);
        $r = $this->rows()[0];
        $this->assertSame('Nova', $r['descricao']);
        $this->assertSame(1000, (int) $r['fin_conc_periodo']);
        $this->assertSame(0, (int) $r['fis_prog_periodo'], 'campo em branco volta a zero');
    }

    public function testMultipleLinesDeleteAndMove(): void
    {
        $this->linha(['descricao' => 'A']);
        $this->linha(['descricao' => 'B', 'meta' => '2']);
        $this->linha(['descricao' => 'C', 'meta' => '3']);
        [$a, $b, $c] = array_map(fn ($r) => (int) $r['id'], $this->rows());
        $this->post("/projetos/{$this->pid}/fisico-financeiro/linhas/$c/mover", ['direcao' => 'cima']);
        $this->assertSame(['A', 'C', 'B'], array_column($this->rows(), 'descricao'));
        $this->post("/projetos/{$this->pid}/fisico-financeiro/linhas/$b/excluir");
        $this->assertSame(['A', 'C'], array_column($this->rows(), 'descricao'));
    }

    public function testCannotTouchLineOfAnotherProject(): void
    {
        $this->linha(['descricao' => 'Do outro'], $this->outro);
        $id = (int) $this->rows($this->outro)[0]['id'];
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/fisico-financeiro/linhas/$id/editar")->status);
        $this->assertSame(404, $this->post("/projetos/{$this->pid}/fisico-financeiro/linhas/$id/excluir")->status);
        $this->assertSame(404, $this->get('/projetos/9999/fisico-financeiro')->status);
        $this->assertCount(1, $this->rows($this->outro));
    }

    public function testGeneratedPdfMatchesSpreadsheetExample(): void
    {
        $this->linha();
        $this->post("/projetos/{$this->pid}/fisico-financeiro/gerar");
        $pdf = $this->pdfPath();
        $this->assertSame(1, (new PdfMergeService())->countPages($pdf));
        $t = Support::texto($pdf);
        foreach (['MUNICÍPIO DE CAXAMBU DO SUL - SC', 'Relatório de Execução Físico-financeira', 'Físico', 'Financeiro', 'No período', 'Até o período',
                  'Realizado no período', 'Realizado até o período', 'OUTROS RENDIMENTO', 'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS',
                  'Caxambu do Sul - SC, 05 de março de 2026', 'MAICON LUFT', 'Contador - CRC/SC 102.316', 'EDI MARCOS'] as $e) {
            $this->assertStringContainsString($e, $t, "PDF deveria conter: $e");
        }
        $this->assertMatchesRegularExpression('/Total\s+Unid\s+1\s+1\s+1\s+1/', $t);
        $this->assertMatchesRegularExpression('/Total\s+497\.152,49\s+-\s+-\s+497\.152,49\s+497\.152,49\s+-\s+497\.152,49/', $t);
    }

    public function testPdfWithDifferentUnitsHasNoPhysicalTotal(): void
    {
        $this->linha(['unidade' => 'm²']);
        $this->linha(['unidade' => 'Unid', 'meta' => '2']);
        $this->post("/projetos/{$this->pid}/fisico-financeiro/gerar");
        $this->assertStringContainsString('Unidades diferentes', Support::texto($this->pdfPath()));
    }

    public function testManyLinesAcrossPages(): void
    {
        for ($i = 1; $i <= 45; $i++) {
            $this->linha(['meta' => (string) $i, 'descricao' => "META NÚMERO $i", 'fin_conc_periodo' => '10,00', 'fin_conc_ate' => '10,00']);
        }
        $this->post("/projetos/{$this->pid}/fisico-financeiro/gerar");
        $pdf = $this->pdfPath();
        $this->assertGreaterThan(1, (new PdfMergeService())->countPages($pdf));
        $t = Support::texto($pdf);
        $this->assertSame(45, preg_match_all('/META NÚMERO \d+/', $t));
        $this->assertStringContainsString('450,00', $t);
    }

    public function testEmptyReportStillGenerates(): void
    {
        $this->post("/projetos/{$this->pid}/fisico-financeiro/gerar");
        $this->assertStringContainsString('Nenhuma meta/etapa.', Support::texto($this->pdfPath()));
    }

    public function testChangesMarkOnlyDocument22Outdated(): void
    {
        $pdo = Database::connection();
        foreach (['01', '15', '22'] as $c) {
            $pdo->exec("INSERT INTO documentos (projeto_id, doc_codigo, caminho, nome_original) VALUES ({$this->pid}, '$c', 'x/$c.pdf', '$c')");
        }
        $this->linha();
        $this->assertSame(['22'], $pdo->query("SELECT doc_codigo FROM documentos WHERE desatualizado = 1")->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function testBalanceteChangesDoNotOutdateDocument22(): void
    {
        $pdo = Database::connection();
        $pdo->exec("INSERT INTO documentos (projeto_id, doc_codigo, caminho, nome_original) VALUES ({$this->pid}, '22', 'x/22.pdf', '22')");
        $this->post("/projetos/{$this->pid}/balancete/lancamentos", ['tipo' => 'despesa', 'origem' => 'convenio', 'descricao' => 'X', 'valor' => '10,00']);
        $this->assertSame(0, (int) $pdo->query("SELECT desatualizado FROM documentos WHERE doc_codigo = '22'")->fetchColumn());
    }

    public function testProjectPageShowsStatusAndLink(): void
    {
        $this->linha();
        $page = $this->get("/projetos/{$this->pid}")->body;
        $this->assertStringContainsString('Abrir físico-financeiro', $page);
        $this->assertStringContainsString('1 linha(s)', $page);
        $this->post("/projetos/{$this->pid}/fisico-financeiro/gerar");
        $res = $this->get("/projetos/{$this->pid}/documentos/22/baixar");
        $this->assertStringContainsString('22-fisico-financeiro.pdf', $res->headers['Content-Disposition']);
    }

    public function testDeletingProjectRemovesLinesAndPdf(): void
    {
        $this->linha();
        $this->post("/projetos/{$this->pid}/fisico-financeiro/gerar");
        $pdf = $this->pdfPath();
        $this->post("/projetos/{$this->pid}/excluir");
        $this->assertCount(0, $this->rows());
        $this->assertFileDoesNotExist($pdf);
    }

    public function testRequiresCsrf(): void
    {
        $this->assertSame(419, handle(new Request('POST', "/projetos/{$this->pid}/fisico-financeiro/linhas", [], ['descricao' => 'x']), 'localhost')->status);
    }

    public function testHtmlInDescriptionIsEscaped(): void
    {
        $this->linha(['descricao' => '<script>alert(1)</script>']);
        $this->assertStringNotContainsString('<script>alert(1)', $this->get("/projetos/{$this->pid}/fisico-financeiro")->body);
        $this->post("/projetos/{$this->pid}/fisico-financeiro/gerar");
        $this->assertStringContainsString('<script>alert(1)</script>', Support::texto($this->pdfPath()));
    }
}
