<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FileStorage;
use App\Services\PdfMergeService;
use PHPUnit\Framework\TestCase;

final class BalanceteFluxoTest extends TestCase
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
        $st = $pdo->prepare("INSERT INTO projetos (municipio_id, concedente, processo, nr_pp, nr_ne, nr_nl, objeto, local, data_assinatura, prefeito_nome, contador_nome, contador_crc)
            VALUES (?, '450001 - Secretaria de Estado da Educação (SED)', 'PROCESSO SCC 7909/2025', '2025PP052544', '2025NE009395', '2025NL116649',
                    'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS PARA O TRANSPORTE ESCOLAR', 'Caxambu do Sul - SC', '2026-03-05', 'EDI MARCOS ANTUNES DE MELLO', 'MAICON LUCAS LUFT', 'CRC/SC 102.316')");
        $st->execute([$mid]);
        $this->pid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'Outro projeto')");
        $this->outro = (int) $pdo->lastInsertId();
    }

    private function post(string $path, array $d = []): Response
    {
        return handle(new Request('POST', $path, [], $d + ['_csrf' => Csrf::token()]), 'localhost');
    }

    private function get(string $path, array $q = []): Response
    {
        return handle(new Request('GET', $path, $q), 'localhost');
    }

    private function lanc(array $o = [], ?int $pid = null): Response
    {
        $pid ??= $this->pid;
        return $this->post("/projetos/$pid/balancete/lancamentos", $o + [
            'tipo' => 'despesa', 'origem' => 'convenio', 'descricao' => 'PAGAMENTO FORN. IVG BRASIL LTDA', 'valor' => '491.186,66',
        ]);
    }

    private function rows(?int $pid = null): array
    {
        $st = Database::connection()->prepare('SELECT * FROM lancamentos WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$pid ?? $this->pid]);
        return $st->fetchAll();
    }

    private function exemplo(): void
    {
        $this->lanc(['tipo' => 'receita', 'descricao' => 'TRANSFERÊNCIA RECEBIDA PROCESSO SCC 007909/2025', 'valor' => '497.152,49', 'doc' => '202509190041924', 'data' => '2025-09-19']);
        $this->lanc(['boletim_parcela' => '1', 'cnpj' => '36519422000620', 'nota_fiscal' => '2797', 'nota_empenho' => '4576/2025', 'doc' => '92301', 'data' => '2025-09-20']);
        $this->lanc(['descricao' => 'IRRF - RETIDO DA EMPRESA IVG BRASIL LTDA', 'valor' => '5.965,83', 'nota_fiscal' => '2797', 'nota_empenho' => '4576/2025', 'doc' => '555302000074997', 'data' => '2025-09-20']);
    }

    private function pdfPath(string $codigo = '01'): string
    {
        $st = Database::connection()->prepare('SELECT caminho FROM documentos WHERE projeto_id = ? AND doc_codigo = ?');
        $st->execute([$this->pid, $codigo]);
        return FileStorage::default()->absolute((string) $st->fetchColumn());
    }

    public function testCreateStoresCentsAndDerivesFonte(): void
    {
        $this->lanc(['origem' => 'contrapartida', 'valor' => '1.234,56']);
        $r = $this->rows()[0];
        $this->assertSame(123456, (int) $r['valor_centavos']);
        $this->assertSame(2, (int) $r['fonte']);
        $this->lanc(['origem' => 'rendimentos']);
        $this->assertSame(1, (int) $this->rows()[1]['fonte']);
    }

    public function testInvalidLancamentoIsNotSavedAndKeepsInput(): void
    {
        $res = $this->lanc(['valor' => 'abc', 'descricao' => 'Algo digitado']);
        $this->assertSame("/projetos/{$this->pid}/balancete/lancamentos/novo", $res->headers['Location']);
        $this->assertCount(0, $this->rows());
        $form = $this->get("/projetos/{$this->pid}/balancete/lancamentos/novo");
        $this->assertStringContainsString('Algo digitado', $form->body);
    }

    public function testSaveAndAddAnotherKeepsContext(): void
    {
        $res = $this->lanc(['outro' => '1', 'origem' => 'contrapartida', 'data' => '2026-01-10']);
        $loc = $res->headers['Location'];
        $this->assertStringContainsString('lancamentos/novo?', $loc);
        $this->assertStringContainsString('origem=contrapartida', $loc);
        $this->assertStringContainsString('data=2026-01-10', $loc);
    }

    public function testEditUpdatesAndShowsValueInBrazilianFormat(): void
    {
        $this->lanc(['valor' => '1.000,50']);
        $id = (int) $this->rows()[0]['id'];
        $form = $this->get("/projetos/{$this->pid}/balancete/lancamentos/$id/editar");
        $this->assertStringContainsString('value="1000,50"', $form->body);
        $this->post("/projetos/{$this->pid}/balancete/lancamentos/$id", ['tipo' => 'receita', 'origem' => 'convenio', 'descricao' => 'Novo', 'valor' => '2.000,00']);
        $r = $this->rows()[0];
        $this->assertSame('Novo', $r['descricao']);
        $this->assertSame('receita', $r['tipo']);
        $this->assertSame(200000, (int) $r['valor_centavos']);
    }

    public function testDeleteAndMove(): void
    {
        $this->lanc(['descricao' => 'A']);
        $this->lanc(['descricao' => 'B']);
        $this->lanc(['descricao' => 'C']);
        [$a, $b, $c] = array_map(fn ($r) => (int) $r['id'], $this->rows());
        $this->post("/projetos/{$this->pid}/balancete/lancamentos/$c/mover", ['direcao' => 'cima']);
        $this->assertSame(['A', 'C', 'B'], array_column($this->rows(), 'descricao'));
        $this->post("/projetos/{$this->pid}/balancete/lancamentos/$a/mover", ['direcao' => 'cima']); // já é o primeiro
        $this->assertSame(['A', 'C', 'B'], array_column($this->rows(), 'descricao'));
        $this->post("/projetos/{$this->pid}/balancete/lancamentos/$c/excluir");
        $this->assertSame(['A', 'B'], array_column($this->rows(), 'descricao'));
    }

    public function testCannotTouchLancamentoOfAnotherProject(): void
    {
        $this->lanc(['descricao' => 'Do outro'], $this->outro);
        $id = (int) $this->rows($this->outro)[0]['id'];
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/balancete/lancamentos/$id/editar")->status);
        $this->assertSame(404, $this->post("/projetos/{$this->pid}/balancete/lancamentos/$id/excluir")->status);
        $this->post("/projetos/{$this->pid}/balancete/lancamentos/$id", ['tipo' => 'despesa', 'origem' => 'convenio', 'descricao' => 'HACK', 'valor' => '1']);
        $this->assertSame('Do outro', $this->rows($this->outro)[0]['descricao']);
        $this->assertSame(404, $this->get('/projetos/9999/balancete')->status);
    }

    public function testPageShowsTotalsFromSpreadsheetExample(): void
    {
        $this->exemplo();
        $body = $this->get("/projetos/{$this->pid}/balancete")->body;
        $this->assertStringContainsString('497.152,49', $body);
        $this->assertStringContainsString('491.186,66', $body);
        $this->assertStringContainsString('5.965,83', $body);
        $this->assertStringContainsString('36.519.422/0006-20', $body);
        $this->assertStringContainsString('19/09/2025', $body);
    }

    public function testGeneratedPdfHasHeaderRowsTotalsAndSignatures(): void
    {
        $this->exemplo();
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $pdf = $this->pdfPath();
        $this->assertSame(1, (new PdfMergeService())->countPages($pdf));
        $t = Support::texto($pdf);
        foreach ([
            'MUNICÍPIO DE CAXAMBU DO SUL - SC', '450001 - Secretaria de Estado da Educação (SED)', '2025PP052544', '2025NE009395', '2025NL116649',
            'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS PARA O TRANSPORTE ESCOLAR',
            'BALANCETE DE PRESTAÇÃO DE CONTAS - RECEITA / DESPESA / SALDO',
            '497.152,49', '491.186,66', '5.965,83', '36.519.422/0006-20', '19/09/2025', '20/09/2025',
            'TOTAL GERAL', 'TOTAL RECURSO DO CONVÊNIO', 'FONTES:', '1 - Concedente', '2 - Convenente',
            'Caxambu do Sul - SC, 05 de março de 2026', 'MAICON LUCAS LUFT', 'Contador - CRC/SC 102.316', 'EDI MARCOS ANTUNES DE MELLO', 'Prefeito',
        ] as $esperado) {
            $this->assertStringContainsString($esperado, $t, "PDF deveria conter: $esperado");
        }
        // Receita total = despesas totais (491.186,66 + 5.965,83 = 497.152,49) => saldo zero
        $this->assertMatchesRegularExpression('/TOTAL GERAL\s+497\.152,49\s+497\.152,49/', $t);
        $this->assertMatchesRegularExpression('/SALDO \(RECEITA - DESPESA\)\s+0,00/', $t);
    }

    public function testPdfIsLandscapeA4(): void
    {
        $this->exemplo();
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $bytes = (string) file_get_contents($this->pdfPath());
        $this->assertSame(1, preg_match('#/MediaBox\s*\[\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*\]#', $bytes, $m));
        $this->assertGreaterThan((float) $m[2], (float) $m[1]);
        $this->assertEqualsWithDelta(841.89, (float) $m[1], 0.5);
    }

    public function testNegativeBalanceAndOrigins(): void
    {
        $this->lanc(['tipo' => 'receita', 'origem' => 'rendimentos', 'descricao' => 'Rend', 'valor' => '100,00']);
        $this->lanc(['tipo' => 'despesa', 'origem' => 'contrapartida', 'descricao' => 'CP', 'valor' => '350,50']);
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $t = Support::texto($this->pdfPath());
        $this->assertMatchesRegularExpression('/SALDO \(RECEITA - DESPESA\)\s+-250,50/', $t);
        $this->assertMatchesRegularExpression('/RENDIMENTOS DA APLICAÇÃO FINANCEIRA\s+100,00\s+-/', $t);
        $this->assertMatchesRegularExpression('/TOTAL RECURSO DE CONTRAPARTIDA\s+-\s+350,50/', $t);
    }

    public function testManyLinesSpanPagesWithSingleTotalsBlock(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->lanc(['descricao' => "PAGAMENTO NÚMERO $i", 'valor' => '10,00']);
        }
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $pdf = $this->pdfPath();
        $this->assertGreaterThan(1, (new PdfMergeService())->countPages($pdf));
        $t = Support::texto($pdf);
        $this->assertSame(1, substr_count($t, 'TOTAL GERAL'));
        $this->assertSame(60, preg_match_all('/PAGAMENTO NÚMERO \d+/', $t));
        $this->assertStringContainsString('600,00', $t);
        $this->assertGreaterThan(1, substr_count($t, 'DESCRIÇÃO'), 'cabeçalho da tabela deve se repetir');
    }

    public function testEmptyBalanceteStillGenerates(): void
    {
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $this->assertStringContainsString('Nenhum lançamento.', Support::texto($this->pdfPath()));
    }

    public function testHtmlInDescriptionIsPrintedLiterally(): void
    {
        $this->lanc(['descricao' => '<b>negrito</b> & "aspas"']);
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $t = Support::texto($this->pdfPath());
        $this->assertStringContainsString('<b>negrito</b> & "aspas"', $t);
        $this->assertStringNotContainsString('<b>', $this->get("/projetos/{$this->pid}/balancete")->body);
    }

    public function testChangingLancamentosMarksDependentReportsOutdated(): void
    {
        $this->exemplo();
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $pdo = Database::connection();
        foreach (['09', '15', '22'] as $c) { // relatórios derivados já gerados (etapas futuras)
            $pdo->exec("INSERT INTO documentos (projeto_id, doc_codigo, caminho, nome_original) VALUES ({$this->pid}, '$c', 'x/$c.pdf', '$c')");
        }
        $pdo->exec("INSERT INTO documentos (projeto_id, doc_codigo, caminho, nome_original) VALUES ({$this->pid}, '18', 'x/18.pdf', '18')");
        $desat = fn () => $pdo->query("SELECT doc_codigo FROM documentos WHERE projeto_id = {$this->pid} AND desatualizado = 1 ORDER BY doc_codigo")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame([], $desat());

        $this->lanc(['descricao' => 'Novo']);
        $this->assertSame(['01', '09', '15'], $desat(), 'o 22 (digitado) e o 18 (upload) não são afetados');
    }

    public function testRegeneratingReplacesOldPdfFile(): void
    {
        $this->exemplo();
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $antigo = $this->pdfPath();
        $this->lanc(['descricao' => 'Mais um']);
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $this->assertFileDoesNotExist($antigo);
        $this->assertFileExists($this->pdfPath());
        $this->assertSame(0, (int) Database::connection()->query("SELECT desatualizado FROM documentos WHERE doc_codigo = '01'")->fetchColumn());
    }

    public function testDownloadAndProjectPageStatus(): void
    {
        $this->exemplo();
        $this->assertStringContainsString('Não gerado', $this->get("/projetos/{$this->pid}")->body);
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $res = $this->get("/projetos/{$this->pid}/documentos/01/baixar");
        $this->assertSame('application/pdf', $res->headers['Content-Type']);
        $this->assertStringContainsString('01-balancete.pdf', $res->headers['Content-Disposition']);
        $page = $this->get("/projetos/{$this->pid}")->body;
        $this->assertStringContainsString('3 lançamento(s)', $page);
        $this->assertStringContainsString('Abrir balancete', $page);
    }

    public function testDeletingProjectRemovesLancamentosAndPdf(): void
    {
        $this->exemplo();
        $this->post("/projetos/{$this->pid}/balancete/gerar");
        $pdf = $this->pdfPath();
        $this->post("/projetos/{$this->pid}/excluir");
        $this->assertCount(0, $this->rows());
        $this->assertFileDoesNotExist($pdf);
    }

    public function testPostsRequireCsrf(): void
    {
        $res = handle(new Request('POST', "/projetos/{$this->pid}/balancete/lancamentos", [], ['tipo' => 'despesa']), 'localhost');
        $this->assertSame(419, $res->status);
    }
}
