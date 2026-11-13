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

    /** Lança uma receita na própria conciliação ($tipo: repasse, contrapartida ou rendimentos). */
    private function receita(string $tipo, string $valor, ?int $pid = null, string $descricao = 'Parcela 1'): Response
    {
        return $this->post('/projetos/' . ($pid ?? $this->pid) . '/conciliacao/receitas', ['tipo' => $tipo, 'descricao' => $descricao, 'valor' => $valor]);
    }

    private function receitas(?int $pid = null): array
    {
        $st = Database::connection()->prepare('SELECT * FROM conciliacao_receitas WHERE projeto_id = ? ORDER BY ordem, id');
        $st->execute([$pid ?? $this->pid]);
        return $st->fetchAll();
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

    public function testRotuloReceitaIsTipoDashDescricao(): void
    {
        $this->assertSame('Repasse - Parcela 1 - OB 123', C::rotuloReceita(['tipo' => 'repasse', 'descricao' => 'Parcela 1 - OB 123']));
        $this->assertSame('Contrapartida', C::rotuloReceita(['tipo' => 'contrapartida', 'descricao' => '  ']));
        $this->assertSame('Rendimentos - Aplicação', C::rotuloReceita(['tipo' => 'rendimentos', 'descricao' => 'Aplicação']));
    }

    public function testValidatorReceita(): void
    {
        $ok = Validator::conciliacaoReceita(['tipo' => 'repasse', 'descricao' => ' Parcela ', 'valor' => '1.234,56']);
        $this->assertSame([], $ok['erros']);
        $this->assertSame(['tipo' => 'repasse', 'descricao' => 'Parcela', 'valor_centavos' => 123456], $ok['dados']);
        $this->assertSame([], Validator::conciliacaoReceita(['tipo' => 'rendimentos', 'valor' => '0,01'])['erros'], 'descrição é opcional');
        foreach ([['tipo' => 'outro', 'valor' => '1,00'], ['tipo' => '', 'valor' => '1,00'], ['tipo' => 'repasse', 'valor' => ''],
                  ['tipo' => 'repasse', 'valor' => '0,00'], ['tipo' => 'repasse', 'valor' => 'abc'], ['tipo' => 'repasse', 'valor' => '-5,00']] as $in) {
            $this->assertNotSame([], Validator::conciliacaoReceita($in)['erros'], json_encode($in));
        }
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

    // ---------- fluxo: receitas digitadas ----------

    public function testReceitasStartEmpty(): void
    {
        $body = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertStringContainsString('Nenhuma receita.', $body);
        $this->assertStringContainsString('Nova receita', $body);
        $this->assertStringNotContainsString('balancete', strtolower($body));
        $this->assertSame([], $this->receitas());
    }

    public function testReceitaIsCreatedListedAndTotaled(): void
    {
        $this->assertSame(303, $this->receita('repasse', '497.152,49', null, 'Parcela 1 - OB 123')->status);
        $this->receita('contrapartida', '1.000,00', null, '');
        $this->receita('rendimentos', '12,34', null, 'Aplicação');
        $this->receita('repasse', '300,00', null, 'Parcela 2'); // vários do mesmo tipo
        $this->assertSame(['repasse', 'contrapartida', 'rendimentos', 'repasse'], array_column($this->receitas(), 'tipo'));
        $body = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        foreach (['Parcela 1 - OB 123', '497.152,49', 'Contrapartida', 'Aplicação', '498.464,83'] as $e) {
            $this->assertStringContainsString($e, $body, $e);
        }
    }

    public function testReceitaCanBeEditedMovedAndDeleted(): void
    {
        $this->receita('repasse', '100,00', null, 'A');
        $this->receita('contrapartida', '200,00', null, 'B');
        [$a, $b] = array_map(fn ($r) => (int) $r['id'], $this->receitas());
        $form = $this->get("/projetos/{$this->pid}/conciliacao/receitas/$a/editar")->body;
        $this->assertStringContainsString('value="100,00"', $form);

        $this->post("/projetos/{$this->pid}/conciliacao/receitas/$a", ['tipo' => 'rendimentos', 'descricao' => 'A2', 'valor' => '150,50']);
        $r = $this->receitas()[0];
        $this->assertSame(['rendimentos', 'A2', 15050], [$r['tipo'], $r['descricao'], (int) $r['valor_centavos']]);

        $this->post("/projetos/{$this->pid}/conciliacao/receitas/$b/mover", ['direcao' => 'cima']);
        $this->assertSame([$b, $a], array_map(fn ($x) => (int) $x['id'], $this->receitas()));
        $this->post("/projetos/{$this->pid}/conciliacao/receitas/$b/mover", ['direcao' => 'cima']); // já é o primeiro
        $this->assertSame([$b, $a], array_map(fn ($x) => (int) $x['id'], $this->receitas()));

        $this->post("/projetos/{$this->pid}/conciliacao/receitas/$b/excluir");
        $this->assertSame([$a], array_map(fn ($x) => (int) $x['id'], $this->receitas()));
    }

    public function testInvalidReceitaIsNotSaved(): void
    {
        foreach ([['outro', '1,00'], ['', '1,00'], ['repasse', ''], ['repasse', '0,00'], ['repasse', 'abc']] as [$tipo, $valor]) {
            $this->assertSame(303, $this->receita($tipo, $valor)->status);
            $flash = Session::pullFlash();
            $this->assertSame('erro', end($flash)['tipo'], "$tipo / $valor");
        }
        $this->assertSame([], $this->receitas());
    }

    public function testReceitaHtmlIsEscaped(): void
    {
        $this->receita('repasse', '1,00', null, '<b>x</b>');
        $this->assertStringNotContainsString('<b>x</b>', $this->get("/projetos/{$this->pid}/conciliacao")->body);
    }

    public function testCannotTouchReceitaOfAnotherProject(): void
    {
        $this->receita('repasse', '5,00', $this->outro);
        $id = (int) $this->receitas($this->outro)[0]['id'];
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/conciliacao/receitas/$id/editar")->status);
        $this->assertSame(404, $this->post("/projetos/{$this->pid}/conciliacao/receitas/$id", ['tipo' => 'repasse', 'valor' => '9,00'])->status);
        $this->assertSame(404, $this->post("/projetos/{$this->pid}/conciliacao/receitas/$id/excluir")->status);
        $this->assertSame(404, $this->get("/projetos/{$this->pid}/conciliacao/receitas/9999/editar")->status);
        $this->assertSame(500, (int) $this->receitas($this->outro)[0]['valor_centavos']);
        $this->assertSame([], $this->receitas());
    }

    public function testBalanceteDoesNotFeedTheConciliacao(): void
    {
        $this->post("/projetos/{$this->pid}/balancete/lancamentos", ['tipo' => 'receita', 'origem' => 'convenio', 'descricao' => 'Transferência', 'valor' => '9.999,00']);
        $this->post("/projetos/{$this->pid}/balancete/lancamentos", ['tipo' => 'despesa', 'origem' => 'convenio', 'descricao' => 'Pagamento', 'valor' => '1.111,00']);
        Session::pullFlash(); // mensagens de confirmação do balancete não fazem parte da página da conciliação
        $body = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertStringContainsString('Nenhuma receita.', $body);
        $this->assertStringNotContainsString('9.999,00', $body);
        $this->assertStringNotContainsString('1.111,00', $body);
        $t = $this->gerarTexto();
        $this->assertStringContainsString('Nenhuma receita informada.', $t);
        $this->assertStringNotContainsString('9.999,00', $t);
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
        $this->receita('repasse', '497.152,49');
        $this->nota();
        $t = $this->gerarTexto();
        $this->assertSame(1, (new PdfMergeService())->countPages($this->pdfPath()));
        foreach (['MUNICÍPIO DE CAXAMBU DO SUL - SC', 'BALANCETE DE VERIFICAÇÃO - CONCILIAÇÃO BANCÁRIA', 'RECEITAS', 'Repasse - Parcela 1',
                  'TOTAL RECEITAS ( 1.1 até 1.1 )', 'DESPESAS', 'Nota Fiscal nº 2797', 'Nota Fiscal - Valor Líquido',
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
        $this->receita('repasse', '10.000,00');
        $this->nota(['nota_fiscal' => '1', 'bruto' => '1.000,00', 'ir' => '']);
        $this->nota(['nota_fiscal' => '2', 'bruto' => '2.500,00', 'ir' => '']);
        $this->nota(['nota_fiscal' => '3', 'bruto' => '500,00', 'ir' => '']);
        $t = $this->gerarTexto();
        $this->assertMatchesRegularExpression('/TOTAL DESPESAS \(2\)\s+4\.000,00\s+4\.000,00/', $t);
        $this->assertMatchesRegularExpression('/SALDO \(RECEITAS - DESPESAS\)\s+6\.000,00/', $t);
    }

    public function testPdfShowsReceitasAndBankSection(): void
    {
        $this->receita('repasse', '1.100,00');
        $this->nota(['nota_fiscal' => '9', 'bruto' => '400,00', 'ir' => '']);
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['saldo_cc' => '600,00', 'saldo_aplic' => '100,00', 'data_saldo' => '2026-04-01', 'extrato_descricao' => 'BANCO X AG 1', 'rotulo_extrato' => 'FINAL - R$', 'titulo' => '3ª PARCELA']);
        $t = $this->gerarTexto();
        $this->assertMatchesRegularExpression('/Repasse - Parcela 1\s+1\.100,00\s+1\.100,00/', $t);
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
        $this->assertStringContainsString('Nenhuma receita informada.', $t);
        $this->assertStringContainsString('TOTAL RECEITAS', $t);
        $this->assertStringNotContainsString('1.1 até', $t);
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
        $this->receita('repasse', '1.000,00');
        $this->nota(['nota_fiscal' => '1', 'bruto' => '300,00', 'ir' => '']);
        $this->post("/projetos/{$this->pid}/conciliacao/config", ['dev_conv_saldo' => '700,00']);
        $body = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertStringContainsString('não fechou', $body);
        $this->assertStringContainsString('não entram', $body);
    }

    public function testNoWarningWhenItCloses(): void
    {
        $this->receita('repasse', '1.000,00');
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
        $this->receita('repasse', '5,00');
        $this->assertSame(1, $out());
        $this->gerarTexto();
        $rid = (int) $this->receitas()[0]['id'];
        $this->post("/projetos/{$this->pid}/conciliacao/receitas/$rid", ['tipo' => 'repasse', 'valor' => '6,00']);
        $this->assertSame(1, $out(), 'editar receita');
        $this->gerarTexto();
        $this->receita('contrapartida', '1,00');
        $id2 = (int) $this->receitas()[1]['id'];
        $this->gerarTexto();
        $this->post("/projetos/{$this->pid}/conciliacao/receitas/$id2/mover", ['direcao' => 'cima']);
        $this->assertSame(1, $out(), 'mover receita');
        $this->gerarTexto();
        $this->post("/projetos/{$this->pid}/conciliacao/receitas/$id2/excluir");
        $this->assertSame(1, $out(), 'excluir receita');
        $this->gerarTexto();
        $this->post("/projetos/{$this->pid}/balancete/lancamentos", ['tipo' => 'receita', 'origem' => 'convenio', 'descricao' => 'X', 'valor' => '10,00']); // balancete
        $this->assertSame(0, $out(), 'mudança no balancete não afeta a conciliação');
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
        foreach (['conciliacao_notas', 'conciliacao_receitas', 'conciliacao'] as $t) {
            $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM $t WHERE projeto_id = {$this->pid}")->fetchColumn(), $t);
        }
        $this->assertFileDoesNotExist($pdf);
    }

    public function testRequiresCsrf(): void
    {
        foreach (['/conciliacao/notas', '/conciliacao/config', '/conciliacao/receitas', '/conciliacao/receitas/1/excluir', '/conciliacao/gerar'] as $p) {
            $this->assertSame(419, handle(new Request('POST', "/projetos/{$this->pid}$p", [], ['x' => 'y']), 'localhost')->status, $p);
        }
    }
}
