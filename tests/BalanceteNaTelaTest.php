<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

/** Balancete: lançar na própria tela (specs/balancete-lancamento-na-tela.md). */
final class BalanceteNaTelaTest extends TestCase
{
    private int $pid;
    private string $b;

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
        $this->b = "/projetos/{$this->pid}/balancete";
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    private function salvar(array $dados): Response
    {
        return handle(new Request('POST', "{$this->b}/lancamentos", [], $dados + ['_csrf' => Csrf::token()]), 'localhost');
    }

    /** HTML do formulário de novo lançamento da tela. */
    private function formulario(string $query = ''): string
    {
        $html = handle(new Request('GET', $this->b, $query === '' ? [] : ['novo' => $query]), 'localhost')->body;
        $this->assertSame(1, preg_match('#<form[^>]*id="form-lancamento".*?</form>#s', $html, $m), 'formulário presente na tela');
        return $m[0];
    }

    public function testScreenHasTheFullFormAndScript(): void
    {
        // CA-BL01
        $html = $this->get($this->b)->body;
        $this->assertStringContainsString('<section class="secao" id="novo-lancamento"', $html);
        $this->assertStringContainsString("action=\"{$this->b}/lancamentos\"", $html);
        $this->assertStringContainsString('src="/assets/lancamento-form.js"', $html);
        $this->assertStringContainsString('<a class="btn" href="#novo-lancamento">Novo lançamento</a>', $html);
        $form = $this->formulario();
        foreach (['name="tipo"', 'name="descricao"', 'name="valor"', 'name="data"', 'id="origem"', 'id="fonte"',
                  'name="boletim_parcela"', 'name="cnpj"', 'name="nota_fiscal"', 'name="nota_empenho"', 'name="doc"', 'name="_csrf"'] as $campo) {
            $this->assertStringContainsString($campo, $form, $campo);
        }
        $this->assertStringContainsString('<details class="recolhe">', $form, 'documentos recolhidos quando vazios');
        $this->assertStringNotContainsString('name="outro"', $form);
        $this->assertStringNotContainsString(' autofocus', $form, 'não rouba a rolagem da tela');
    }

    public function testSavingReturnsToScreenWithMessageHighlightAndCleanForm(): void
    {
        // CA-BL02
        $res = $this->salvar(['tipo' => 'despesa', 'origem' => 'convenio', 'descricao' => 'Aquisição de ônibus', 'valor' => '491.186,66', 'data' => '2025-10-01']);
        $lid = (int) Database::connection()->query("SELECT id FROM lancamentos WHERE projeto_id = {$this->pid}")->fetchColumn();
        $this->assertSame("{$this->b}?novo=$lid#novo-lancamento", $res->headers['Location']);
        $this->assertStringContainsString('Lançamento adicionado: Aquisição de ônibus — R$ 491.186,66', json_encode($_SESSION, JSON_UNESCAPED_UNICODE));

        $html = handle(new Request('GET', $this->b, ['novo' => (string) $lid]), 'localhost')->body;
        $this->assertStringContainsString("<tr id=\"l$lid\" class=\"linha-nova\">", $html);
        $this->assertStringContainsString('Aquisição de ônibus <span class="tag info">novo</span>', $html);
        $this->assertStringContainsString('<span class="kpi-rotulo">Despesa total</span><span class="kpi-valor">491.186,66</span>', $html);

        $form = $this->formulario((string) $lid);
        $this->assertStringContainsString('name="descricao" required maxlength="300" value=""', $form);
        $this->assertStringContainsString('name="valor" required inputmode="decimal" placeholder="1.234,56" value=""', $form);
        $this->assertStringContainsString('name="data" value=""', $form);
    }

    public function testValidationErrorKeepsTypedValuesOnTheScreen(): void
    {
        // CA-BL03
        $res = $this->salvar(['tipo' => 'receita', 'origem' => 'contrapartida', 'fonte' => '2', 'descricao' => 'Algo digitado', 'valor' => 'abc', 'nota_fiscal' => 'NF-77']);
        $this->assertSame("{$this->b}#novo-lancamento", $res->headers['Location']);
        $this->assertSame(0, (int) Database::connection()->query('SELECT COUNT(*) FROM lancamentos')->fetchColumn());

        $form = $this->formulario();
        $this->assertStringContainsString('value="Algo digitado"', $form);
        $this->assertStringContainsString('value="abc"', $form);
        $this->assertStringContainsString('value="receita" checked', $form);
        $this->assertStringContainsString('<option value="contrapartida" selected>', $form);
        $this->assertStringContainsString('<details class="recolhe" open>', $form, 'documentos abertos quando preenchidos');
        $this->assertStringContainsString('value="NF-77"', $form);

        // a mensagem de erro aparece só uma vez e o formulário seguinte volta limpo
        $this->assertStringNotContainsString('Algo digitado', $this->formulario());
    }

    public function testEditStaysOnItsOwnPage(): void
    {
        // CA-BL04
        $this->salvar(['tipo' => 'despesa', 'origem' => 'convenio', 'descricao' => 'Original', 'valor' => '10,00', 'cnpj' => '36.519.422/0006-20']);
        $lid = (int) Database::connection()->query('SELECT id FROM lancamentos')->fetchColumn();
        $html = $this->get("{$this->b}/lancamentos/$lid/editar")->body;
        $this->assertStringContainsString('<h1>Editar lançamento</h1>', $html);
        $this->assertStringContainsString('value="Original" autofocus', $html);
        $this->assertStringContainsString('value="10,00"', $html);
        $this->assertStringContainsString('<details class="recolhe" open>', $html, 'documentos abertos porque há CNPJ');
        $this->assertStringContainsString(">Cancelar</a>", $html);
        $this->assertStringNotContainsString('name="outro"', $html);
    }

    public function testOldNewAddressRedirectsToTheForm(): void
    {
        // DEC-BL3
        $res = $this->get("{$this->b}/lancamentos/novo");
        $this->assertSame(303, $res->status);
        $this->assertSame("{$this->b}#novo-lancamento", $res->headers['Location']);
    }

    public function testHighlightIgnoresUnknownIds(): void
    {
        $this->salvar(['tipo' => 'despesa', 'origem' => 'convenio', 'descricao' => 'A', 'valor' => '1,00']);
        $html = handle(new Request('GET', $this->b, ['novo' => '999999']), 'localhost')->body;
        $this->assertStringNotContainsString('linha-nova', $html);
        $this->assertStringNotContainsString('<span class="tag info">novo</span>', $html);
    }
}
