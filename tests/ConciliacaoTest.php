<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\ConciliacaoCalculator as C;
use App\Domain\Validator;
use App\Services\FileStorage;
use App\Services\PdfMergeService;
use PHPUnit\Framework\TestCase;

final class ConciliacaoTest extends TestCase
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
        $pdo->exec("INSERT INTO projetos (municipio_id, concedente, objeto, local, data_assinatura, prefeito_nome, contador_nome, contador_crc)
                    VALUES ($mid, '450001 - SED', 'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', 'Caxambu do Sul - SC', '2026-03-05', 'EDI MARCOS', 'MAICON LUFT', 'CRC/SC 102.316')");
        $this->pid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) VALUES ($mid, 'Outro')");
        $this->outro = (int) $pdo->lastInsertId();
    }

    // ---------- helpers ----------

    private function post(string $path, array $d = []): Response
    {
        return handle(new Request('POST', $path, [], $d + ['_csrf' => Csrf::token()]), 'localhost');
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    private function receita(string $origem, string $valor, ?int $pid = null): void
    {
        $this->post('/projetos/' . ($pid ?? $this->pid) . '/balancete/lancamentos', ['tipo' => 'receita', 'origem' => $origem, 'descricao' => "Receita $origem", 'valor' => $valor]);
    }

    private function nota(array $o = [], ?int $pid = null): Response
    {
        return $this->post('/projetos/' . ($pid ?? $this->pid) . '/conciliacao/notas', $o + ['nota_fiscal' => '2797', 'bruto' => '497.152,49', 'ir' => '5.965,83']);
    }

    private function notas(?int $pid = null): array
    {
        $st = Database::connection()->prepare('SELECT * FROM conciliacao_notas WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$pid ?? $this->pid]);
        return $st->fetchAll();
    }

    private function overrides(): array
    {
        $st = Database::connection()->prepare('SELECT campo, valor_centavos FROM overrides WHERE projeto_id = ?');
        $st->execute([$this->pid]);
        return array_map('intval', $st->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    private function pdfPath(): string
    {
        $st = Database::connection()->prepare("SELECT caminho FROM documentos WHERE projeto_id = ? AND doc_codigo = '15'");
        $st->execute([$this->pid]);
        return FileStorage::default()->absolute((string) $st->fetchColumn());
    }

    private function gerarTexto(): string
    {
        $this->post("/projetos/{$this->pid}/conciliacao/gerar");
        return Support::texto($this->pdfPath());
    }

    // ---------- domínio ----------

    public function testReceitasAreFilledToThreeSlotsEachAndKeepOrder(): void
    {
        $l = [
            ['id' => 5, 'tipo' => 'receita', 'origem' => 'convenio', 'valor_centavos' => 1000],
            ['id' => 6, 'tipo' => 'despesa', 'origem' => 'convenio', 'valor_centavos' => 999],   // ignorada
            ['id' => 7, 'tipo' => 'receita', 'origem' => 'rendimentos', 'valor_centavos' => 30],
            ['id' => 8, 'tipo' => 'receita', 'origem' => 'rendimentos', 'valor_centavos' => 20],
        ];
        $r = C::receitasCalculadas($l);
        $this->assertCount(7, $r);
        $this->assertSame(['1º Repasse', '2º Repasse', '3º Repasse', '1ª Contrapartida', '2ª Contrapartida', '3ª Contrapartida', 'Rendimentos Aplicação Financeira'], array_column($r, 'rotulo'));
        $this->assertSame([1000, 0, 0, 0, 0, 0, 50], array_column($r, 'calculado'));
        $this->assertSame('rep-5', $r[0]['chave']);
        $this->assertSame('rep-slot-2', $r[1]['chave']);
        $this->assertSame('cp-slot-1', $r[3]['chave']);
        $this->assertSame('rend', $r[6]['chave']);
    }

    public function testMoreThanThreeRepassesAreAllListed(): void
    {
        $l = [];
        foreach ([1, 2, 3, 4] as $i) {
            $l[] = ['id' => $i, 'tipo' => 'receita', 'origem' => 'convenio', 'valor_centavos' => $i * 100];
        }
        $r = C::receitasCalculadas($l);
        $this->assertCount(8, $r);
        $this->assertSame('4º Repasse', $r[3]['rotulo']);
    }

    public function testOverridesOnlyMarkRealDifferences(): void
    {
        $r = C::aplicarOverrides(C::receitasCalculadas([['id' => 1, 'tipo' => 'receita', 'origem' => 'convenio', 'valor_centavos' => 1000]]), ['rep-1' => 1500, 'rep-slot-2' => 0, 'cp-slot-1' => 50]);
        $this->assertSame(1500, $r[0]['valor']);
        $this->assertTrue($r[0]['alterado']);
        $this->assertFalse($r[1]['alterado'], 'override igual ao calculado não conta como alterado');
        $this->assertTrue($r[3]['alterado']);
        $this->assertSame(1000, $r[0]['calculado']);
    }

    public function testBlocoNotaSomaColumnAccumulatesBackToGross(): void
    {
        $b = C::blocoNota(['nota_fiscal' => '2797', 'bruto_centavos' => 49715249, 'ir_centavos' => 596583, 'iss_centavos' => 0, 'inss_centavos' => 0, 'outros_centavos' => 0]);
        $this->assertSame(['Nota Fiscal nº 2797', 'Nota Fiscal - Valor Líquido', 'IR sobre Nota Fiscal', 'ISS sobre Nota Fiscal', 'INSS sobre Nota Fiscal'], array_column($b, 'rotulo'));
        $this->assertSame([49715249, 49118666, 596583, 0, 0], array_column($b, 'valor'));
        $this->assertSame([49715249, 49118666, 49715249, 49715249, 49715249], array_column($b, 'soma'));
    }

    public function testBlocoNotaShowsOtherDiscountsOnlyWhenPresent(): void
    {
        $b = C::blocoNota(['nota_fiscal' => '1', 'bruto_centavos' => 10000, 'ir_centavos' => 100, 'iss_centavos' => 200, 'inss_centavos' => 300, 'outros_centavos' => 400]);
        $this->assertSame('Outros descontos', end($b)['rotulo']);
        $this->assertSame(10000, end($b)['soma']);
        $this->assertSame(9000, $b[1]['valor']);
    }

    public function testTotalDespesasSumsAllNotasNotJustTheFirst(): void
    {
        // Defeito da planilha: C160 = C21 contava só a 1ª nota.
        $notas = [
            ['bruto_centavos' => 100000], ['bruto_centavos' => 250000], ['bruto_centavos' => 50000],
        ];
        $cfg = array_fill_keys(['saldo_cc', 'saldo_aplic', ...array_keys(C::DEVOLUCOES)], 0);
        $t = C::totais([['valor' => 500000]], $notas, $cfg);
        $this->assertSame(400000, $t['despesas']);
        $this->assertSame(100000, $t['saldo']);
    }

    public function testDevolucoesAreOutsideTotalDespesasAsDecided(): void
    {
        $cfg = array_fill_keys(['saldo_cc', 'saldo_aplic', ...array_keys(C::DEVOLUCOES)], 0);
        $cfg['dev_conv_saldo'] = 70000;
        $t = C::totais([['valor' => 100000]], [['bruto_centavos' => 30000]], $cfg);
        $this->assertSame(30000, $t['despesas']);
        $this->assertSame(70000, $t['devolucoes']);
        $this->assertSame(70000, $t['conciliacao'], 'com saldo bancário zero, a devolução aparece como diferença');
    }

    public function testConciliacaoIsZeroWhenSaldoMatchesBank(): void
    {
        $cfg = array_fill_keys(['saldo_cc', 'saldo_aplic', ...array_keys(C::DEVOLUCOES)], 0);
        $cfg['saldo_cc'] = 60000;
        $cfg['saldo_aplic'] = 10000;
        $t = C::totais([['valor' => 100000]], [['bruto_centavos' => 30000]], $cfg);
        $this->assertSame(70000, $t['bancario']);
        $this->assertSame(0, $t['conciliacao']);
    }

    public function testNotaValidator(): void
    {
        $ok = Validator::conciliacaoNota(['nota_fiscal' => ' 2797 ', 'bruto' => '1.000,00', 'ir' => '10,50']);
        $this->assertSame([], $ok['erros']);
        $this->assertSame(100000, $ok['dados']['bruto_centavos']);
        $this->assertSame(0, $ok['dados']['iss_centavos']);

        $this->assertNotEmpty(Validator::conciliacaoNota(['nota_fiscal' => '', 'bruto' => '10'])['erros']);
        $this->assertNotEmpty(Validator::conciliacaoNota(['nota_fiscal' => '1', 'bruto' => '0'])['erros']);
        $this->assertNotEmpty(Validator::conciliacaoNota(['nota_fiscal' => '1', 'bruto' => '100', 'ir' => '80', 'iss' => '30'])['erros'], 'retenções > bruto');
        $this->assertNotEmpty(Validator::conciliacaoNota(['nota_fiscal' => '1', 'bruto' => 'abc'])['erros']);
    }

    // ---------- fluxo: notas ----------

    public function testNotaCrud(): void
    {
        $this->nota();
        $this->nota(['nota_fiscal' => '3000', 'bruto' => '1.000,00', 'ir' => '']);
        [$a, $b] = array_map(fn ($n) => (int) $n['id'], $this->notas());
        $this->assertSame(49715249, (int) $this->notas()[0]['bruto_centavos']);

        $form = $this->get("/projetos/{$this->pid}/conciliacao/notas/$a/editar")->body;
        $this->assertStringContainsString('value="497152,49"', $form);
        $this->post("/projetos/{$this->pid}/conciliacao/notas/$a", ['nota_fiscal' => '2797-A', 'bruto' => '500,00']);
        $this->assertSame('2797-A', $this->notas()[0]['nota_fiscal']);
        $this->assertSame(0, (int) $this->notas()[0]['ir_centavos']);

        $this->post("/projetos/{$this->pid}/conciliacao/notas/$b/mover", ['direcao' => 'cima']);
        $this->assertSame(['3000', '2797-A'], array_column($this->notas(), 'nota_fiscal'));
        $this->post("/projetos/{$this->pid}/conciliacao/notas/$a/excluir");
        $this->assertSame(['3000'], array_column($this->notas(), 'nota_fiscal'));
    }

    public function testInvalidNoteIsRejected(): void
    {
        $res = $this->nota(['bruto' => '100,00', 'ir' => '200,00']);
        $this->assertSame("/projetos/{$this->pid}/conciliacao/notas/novo", $res->headers['Location']);
        $this->assertCount(0, $this->notas());
    }

    public function testCannotTouchNoteOfAnotherProject(): void
    {
        $this->nota([], $this->outro);
        $id = (int) $this->notas($this->outro)[0]['id'];
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/conciliacao/notas/$id/editar")->status);
        $this->assertSame(404, $this->post("/projetos/{$this->pid}/conciliacao/notas/$id/excluir")->status);
        $this->assertSame(404, $this->get('/projetos/9999/conciliacao')->status);
        $this->assertCount(1, $this->notas($this->outro));
    }

    // ---------- fluxo: receitas e sobrescritas ----------

    public function testReceitasComeFromBalanceteAndShowOnPage(): void
    {
        $this->receita('convenio', '497.152,49');
        $this->receita('contrapartida', '1.000,00');
        $body = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertStringContainsString('1º Repasse', $body);
        $this->assertStringContainsString('497.152,49', $body);
        $this->assertStringContainsString('1ª Contrapartida', $body);
        $this->assertStringNotContainsString('class="tag aviso-tag" title="Diferente do balancete"', $body);
    }

    public function testOverrideIsSavedMarkedAndRestorable(): void
    {
        $this->receita('convenio', '1.000,00');
        $key = (string) array_key_first(array_flip(array_column(C::receitasCalculadas(Database::connection()->query('SELECT * FROM lancamentos')->fetchAll()), 'chave')));
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => [$key => '1.200,00', 'rep-slot-2' => '0,00']]);
        $this->assertSame([$key => 120000], $this->overrides(), '0,00 igual ao calculado não gera sobrescrita');
        $this->assertStringContainsString('title="Diferente do balancete"', $this->get("/projetos/{$this->pid}/conciliacao")->body);

        $this->post("/projetos/{$this->pid}/conciliacao/receitas/restaurar", ['chave' => $key]);
        $this->assertSame([], $this->overrides());
    }

    public function testTypingTheCalculatedValueRemovesOverride(): void
    {
        $this->receita('convenio', '1.000,00');
        $key = Database::connection()->query('SELECT id FROM lancamentos')->fetchColumn();
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => ["rep-$key" => '999,00']]);
        $this->assertCount(1, $this->overrides());
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => ["rep-$key" => '1000,00']]);
        $this->assertSame([], $this->overrides());
    }

    public function testManualValueInEmptySlot(): void
    {
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => ['rep-slot-2' => '250,00']]);
        $this->assertSame(['rep-slot-2' => 25000], $this->overrides());
    }

    public function testInvalidOverrideSavesNothing(): void
    {
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => ['rep-slot-1' => '100,00', 'rep-slot-2' => 'abc']]);
        $this->assertSame([], $this->overrides());
    }

    public function testUnknownKeysAreIgnoredAndRestoreRejectsThem(): void
    {
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => ['hack' => '5,00', "x'; DROP" => '1']]);
        $this->assertSame([], $this->overrides());
        $this->post("/projetos/{$this->pid}/conciliacao/receitas/restaurar", ['chave' => 'hack']);
        $flash = Session::pullFlash();
        $this->assertSame('erro', end($flash)['tipo']);
    }

    public function testOverridesAreIsolatedPerProject(): void
    {
        $this->post("/projetos/{$this->outro}/conciliacao/receitas", ['valores' => ['rep-slot-1' => '999,00']]);
        $this->assertSame([], $this->overrides());
        $st = Database::connection()->query("SELECT COUNT(*) FROM overrides WHERE projeto_id = {$this->outro}");
        $this->assertSame(1, (int) $st->fetchColumn());
    }

    // ---------- fluxo: dados avulsos ----------

    public function testConfigDefaultsAndSave(): void
    {
        $body = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertStringContainsString('PRESTAÇÃO DE CONTAS FINAL - ENCERRAMENTO DE CONVÊNIO', $body);
        $this->assertStringContainsString('value="2026-03-05"', $body, 'data do saldo = data do projeto');

        $this->post("/projetos/{$this->pid}/conciliacao/config", [
            'titulo' => '3ª PARCELA', 'rotulo_extrato' => 'FINAL - R$', 'extrato_descricao' => 'BB 123', 'data_saldo' => '2026-04-01',
            'saldo_cc' => '1.000,50', 'saldo_aplic' => '', 'dev_conv_saldo' => '70,00',
        ]);
        $row = Database::connection()->query("SELECT * FROM conciliacao WHERE projeto_id = {$this->pid}")->fetch();
        $this->assertSame(100050, (int) $row['saldo_cc']);
        $this->assertSame(0, (int) $row['saldo_aplic']);
        $this->assertSame(7000, (int) $row['dev_conv_saldo']);
        $this->assertStringContainsString('value="1000,50"', $this->get("/projetos/{$this->pid}/conciliacao")->body);
    }

    public function testInvalidConfigIsNotSaved(): void
    {
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['saldo_cc' => 'abc', 'data_saldo' => '2026-13-45']);
        $this->assertSame(0, (int) Database::connection()->query('SELECT COUNT(*) FROM conciliacao')->fetchColumn());
    }

    // ---------- PDF ----------

    public function testPdfReproducesSpreadsheetExampleOnOnePage(): void
    {
        $this->receita('convenio', '497.152,49');
        $this->nota();
        $t = $this->gerarTexto();
        $this->assertSame(1, (new PdfMergeService())->countPages($this->pdfPath()));
        foreach (['MUNICÍPIO DE CAXAMBU DO SUL - SC', 'BALANCETE DE VERIFICAÇÃO - CONCILIAÇÃO BANCÁRIA', 'RECEITAS', '1º Repasse', '3ª Contrapartida',
                  'Rendimentos Aplicação Financeira', 'TOTAL RECEITAS ( 1.1 até 1.7 )', 'DESPESAS', 'Nota Fiscal nº 2797', 'Nota Fiscal - Valor Líquido',
                  'IR sobre Nota Fiscal', 'ISS sobre Nota Fiscal', 'INSS sobre Nota Fiscal', 'Devolução Concedente Rendimento de Aplicação',
                  'Devolução Convenente Saldo Remanescente', 'TOTAL DESPESAS (2)', 'SALDO (RECEITAS - DESPESAS)', 'EXTRATO BANCÁRIO',
                  'Saldo Conta Corrente em 05/03/2026', 'Saldo Conta Aplicações/Investimentos em 05/03/2026', 'Saldo em Conta Bancária (4.1 + 4.2)',
                  'Conciliação Bancária (3.0 - 4.0)', 'Caxambu do Sul - SC, em 05 de março de 2026', 'EDI MARCOS', 'Prefeito Municipal'] as $e) {
            $this->assertStringContainsString($e, $t, "PDF deveria conter: $e");
        }
        $this->assertMatchesRegularExpression('/Nota Fiscal nº 2797\s+497\.152,49\s+497\.152,49/', $t);
        $this->assertMatchesRegularExpression('/Nota Fiscal - Valor Líquido\s+491\.186,66\s+491\.186,66/', $t);
        $this->assertMatchesRegularExpression('/IR sobre Nota Fiscal\s+5\.965,83\s+497\.152,49/', $t);
        $this->assertMatchesRegularExpression('/TOTAL DESPESAS \(2\)\s+497\.152,49\s+497\.152,49/', $t);
        $this->assertMatchesRegularExpression('/SALDO \(RECEITAS - DESPESAS\)\s+0,00\s+0,00/', $t);
        $this->assertMatchesRegularExpression('/Conciliação Bancária \(3\.0 - 4\.0\)\s+0,00\s+0,00/', $t);
    }

    public function testPdfIsPortraitAndOnlyPrefeitoSigns(): void
    {
        $t = $this->gerarTexto();
        $bytes = (string) file_get_contents($this->pdfPath());
        $this->assertSame(1, preg_match('#/MediaBox\s*\[\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*\]#', $bytes, $m));
        $this->assertLessThan((float) $m[2], (float) $m[1], 'retrato');
        $this->assertStringContainsString('Prefeito Municipal', $t);
        $this->assertStringNotContainsString('Contador', $t);
        $this->assertStringNotContainsString('MAICON LUFT', $t);
    }

    public function testPdfTotalDespesasSumsEveryNota(): void
    {
        $this->receita('convenio', '10.000,00');
        $this->nota(['nota_fiscal' => '1', 'bruto' => '1.000,00', 'ir' => '']);
        $this->nota(['nota_fiscal' => '2', 'bruto' => '2.500,00', 'ir' => '']);
        $this->nota(['nota_fiscal' => '3', 'bruto' => '500,00', 'ir' => '']);
        $t = $this->gerarTexto();
        $this->assertMatchesRegularExpression('/TOTAL DESPESAS \(2\)\s+4\.000,00\s+4\.000,00/', $t);
        $this->assertMatchesRegularExpression('/SALDO \(RECEITAS - DESPESAS\)\s+6\.000,00/', $t);
    }

    public function testPdfShowsOverrideAndBankSection(): void
    {
        $this->receita('convenio', '1.000,00');
        $key = (string) Database::connection()->query('SELECT id FROM lancamentos')->fetchColumn();
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => ["rep-$key" => '1.100,00']]);
        $this->nota(['nota_fiscal' => '9', 'bruto' => '400,00', 'ir' => '']);
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['saldo_cc' => '600,00', 'saldo_aplic' => '100,00', 'data_saldo' => '2026-04-01', 'extrato_descricao' => 'BANCO X AG 1', 'rotulo_extrato' => 'FINAL - R$', 'titulo' => '3ª PARCELA']);
        $t = $this->gerarTexto();
        $this->assertMatchesRegularExpression('/1º Repasse\s+1\.100,00\s+1\.100,00/', $t);
        $this->assertStringContainsString('EXTRATO BANCÁRIO - BANCO X AG 1', $t);
        $this->assertStringContainsString('Saldo Conta Corrente em 01/04/2026', $t);
        $this->assertStringContainsString('3ª PARCELA', $t);
        $this->assertMatchesRegularExpression('/Saldo em Conta Bancária \(4\.1 \+ 4\.2\)\s+700,00/', $t);
        $this->assertMatchesRegularExpression('/Conciliação Bancária \(3\.0 - 4\.0\)\s+0,00/', $t);
    }

    public function testManyNotasSpanPages(): void
    {
        for ($i = 1; $i <= 40; $i++) {
            $this->nota(['nota_fiscal' => "NF$i", 'bruto' => '100,00', 'ir' => '1,00', 'iss' => '2,00', 'inss' => '3,00']);
        }
        $t = $this->gerarTexto();
        $this->assertGreaterThan(1, (new PdfMergeService())->countPages($this->pdfPath()));
        $this->assertSame(40, preg_match_all('/Nota Fiscal nº NF\d+/', $t));
        $this->assertSame(1, substr_count($t, 'TOTAL DESPESAS (2)'));
        $this->assertMatchesRegularExpression('/TOTAL DESPESAS \(2\)\s+4\.000,00/', $t);
    }

    /** Regressão: o extrato bancário e a conciliação nunca podem ser divididos entre páginas. */
    public function testExtratoSectionIsNeverSplitAcrossPages(): void
    {
        foreach ([2, 5, 8, 11, 14, 20] as $quantidade) {
            Database::connection()->exec("DELETE FROM conciliacao_notas WHERE projeto_id = {$this->pid}");
            for ($i = 1; $i <= $quantidade; $i++) {
                $this->nota(['nota_fiscal' => "NF$i", 'bruto' => '100,00', 'ir' => '1,00']);
            }
            $this->post("/projetos/{$this->pid}/conciliacao/gerar");
            $pdf = $this->pdfPath();
            $paginas = (new PdfMergeService())->countPages($pdf);
            $comExtrato = [];
            for ($p = 1; $p <= $paginas; $p++) {
                $t = Support::textoPagina($pdf, $p);
                if (str_contains($t, 'EXTRATO BANCÁRIO')) {
                    $comExtrato[] = $t;
                }
            }
            $this->assertCount(1, $comExtrato, "$quantidade notas: o extrato deve aparecer em uma única página");
            foreach (['Saldo Conta Corrente', 'Saldo em Conta Bancária', 'Conciliação Bancária (3.0', 'Prefeito Municipal'] as $parte) {
                $this->assertStringContainsString($parte, $comExtrato[0], "$quantidade notas: «{$parte}» deve estar na mesma página do extrato");
            }
        }
    }

    public function testEmptyConciliacaoGenerates(): void
    {
        $t = $this->gerarTexto();
        $this->assertStringContainsString('Nenhuma nota fiscal informada.', $t);
        $this->assertStringContainsString('1.0', $t);
    }

    public function testHtmlIsPrintedLiterally(): void
    {
        $this->nota(['nota_fiscal' => '<b>7</b>']);
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['extrato_descricao' => '<i>BB</i>']);
        $t = $this->gerarTexto();
        $this->assertStringContainsString('Nota Fiscal nº <b>7</b>', $t);
        $this->assertStringContainsString('<i>BB</i>', $t);
        $this->assertStringNotContainsString('<b>7</b>', $this->get("/projetos/{$this->pid}/conciliacao")->body);
    }

    // ---------- integração ----------

    public function testWarningWhenConciliationDoesNotCloseExplainsDevolucoes(): void
    {
        $this->receita('convenio', '1.000,00');
        $this->nota(['nota_fiscal' => '1', 'bruto' => '300,00', 'ir' => '']);
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['dev_conv_saldo' => '700,00']);
        $body = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertStringContainsString('não fechou', $body);
        $this->assertStringContainsString('não entram', $body);
    }

    public function testNoWarningWhenItCloses(): void
    {
        $this->receita('convenio', '1.000,00');
        $this->nota(['nota_fiscal' => '1', 'bruto' => '300,00', 'ir' => '']);
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['saldo_cc' => '700,00']);
        $this->assertStringNotContainsString('não fechou', $this->get("/projetos/{$this->pid}/conciliacao")->body);
    }

    public function testChangesMarkDocument15Outdated(): void
    {
        $pdo = Database::connection();
        $this->gerarTexto();
        $out = fn () => (int) $pdo->query("SELECT desatualizado FROM documentos WHERE doc_codigo = '15'")->fetchColumn();
        $this->assertSame(0, $out());
        $this->nota();
        $this->assertSame(1, $out());
        $this->gerarTexto();
        $this->assertSame(0, $out());
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['saldo_cc' => '1,00']);
        $this->assertSame(1, $out());
        $this->gerarTexto();
        $this->post("/projetos/{$this->pid}/conciliacao/receitas", ['valores' => ['rep-slot-1' => '5,00']]);
        $this->assertSame(1, $out());
        $this->gerarTexto();
        $this->receita('convenio', '10,00'); // mudança no balancete
        $this->assertSame(1, $out());
    }

    public function testProjectPageStatusDownloadAndCascade(): void
    {
        $this->nota();
        $page = $this->get("/projetos/{$this->pid}")->body;
        $this->assertStringContainsString('Abrir conciliação', $page);
        $this->assertStringContainsString('1 nota(s) fiscal(is)', $page);
        $this->gerarTexto();
        $res = $this->get("/projetos/{$this->pid}/documentos/15/baixar");
        $this->assertStringContainsString('15-conciliacao-bancaria.pdf', $res->headers['Content-Disposition']);

        $pdf = $this->pdfPath();
        $this->post("/projetos/{$this->pid}/excluir");
        $pdo = Database::connection();
        foreach (['conciliacao_notas', 'conciliacao', 'overrides'] as $t) {
            $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM $t WHERE projeto_id = {$this->pid}")->fetchColumn(), $t);
        }
        $this->assertFileDoesNotExist($pdf);
    }

    public function testRequiresCsrf(): void
    {
        foreach (['/conciliacao/notas', '/conciliacao/config', '/conciliacao/receitas', '/conciliacao/gerar'] as $p) {
            $this->assertSame(419, handle(new Request('POST', "/projetos/{$this->pid}$p", [], ['x' => 'y']), 'localhost')->status, $p);
        }
    }
}
