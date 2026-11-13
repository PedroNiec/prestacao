<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\FileStorage;
use PHPUnit\Framework\TestCase;

/** Relação de bens, doc. 09 (specs/relacao-de-bens.md). */
final class RelacaoBensTest extends TestCase
{
    private int $pid;
    private string $rb;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Caxambu do Sul', 'SC')");
        $mid = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO projetos (municipio_id, objeto, local, data_assinatura, contador_nome, contador_crc, prefeito_nome) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$mid, 'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', 'Caxambu do Sul - SC', '2026-03-05', 'MAICON LUCAS LUFT', 'CRC/SC 102.316', 'EDI MARCOS']);
        $this->pid = (int) $pdo->lastInsertId();
        $this->rb = "/projetos/{$this->pid}/relacao-de-bens";
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    private function post(string $path, array $data = []): Response
    {
        return handle(new Request('POST', $path, [], $data + ['_csrf' => Csrf::token()]), 'localhost');
    }

    private function criar(string $descricao, string $valor): Response
    {
        return $this->post("{$this->rb}/bens", ['descricao' => $descricao, 'valor' => $valor]);
    }

    /** @return array<int,array<string,mixed>> */
    private function bens(): array
    {
        $st = Database::connection()->prepare('SELECT * FROM bens WHERE projeto_id = ? ORDER BY ordem');
        $st->execute([$this->pid]);
        return $st->fetchAll();
    }

    private function situacao09(): ?array
    {
        $st = Database::connection()->prepare("SELECT * FROM documentos WHERE projeto_id = ? AND doc_codigo = '09'");
        $st->execute([$this->pid]);
        return $st->fetch() ?: null;
    }

    private function htmlPdf(): string
    {
        $st =Database::connection()->prepare('SELECT p.*, m.nome AS municipio_nome, m.uf AS municipio_uf FROM projetos p JOIN municipios m ON m.id = p.municipio_id WHERE p.id = ?');
        $st->execute([$this->pid]);
        $projeto = $st->fetch();
        $bens = $this->bens();
        $titulo = (string) (Database::connection()->query("SELECT titulo FROM bens_config WHERE projeto_id = {$this->pid}")->fetchColumn() ?: 'RELAÇÃO DE BENS ADQUIRIDOS');
        return View::render('pdf/relacao_bens', [
            'projeto' => $projeto, 'bens' => $bens, 'total' => array_sum(array_column($bens, 'valor_centavos')), 'tituloDocumento' => $titulo,
        ], false);
    }

    public function testCrudAndOrderingRenumberItems(): void
    {
        // CA-B01
        $this->assertSame(303, $this->criar('Ônibus escolar', '497.152,49')->status);
        $this->criar('Pneus', '1.000,00');
        $this->criar('Extintor', '250,50');
        [$a, $b, $c] = $this->bens();
        $this->assertSame(['Ônibus escolar', 'Pneus', 'Extintor'], [$a['descricao'], $b['descricao'], $c['descricao']]);
        $this->assertSame(49715249, (int) $a['valor_centavos']);

        $this->post("{$this->rb}/bens/{$c['id']}/mover", ['direcao' => 'cima']);
        $this->assertSame(['Ônibus escolar', 'Extintor', 'Pneus'], array_column($this->bens(), 'descricao'));

        $this->post("{$this->rb}/bens/{$b['id']}", ['descricao' => 'Pneus novos', 'valor' => '1.100,00']);
        $this->post("{$this->rb}/bens/{$a['id']}/excluir");
        $this->assertSame(['Extintor', 'Pneus novos'], array_column($this->bens(), 'descricao'));

        $html = $this->get($this->rb)->body;
        $this->assertMatchesRegularExpression('#<tr id="b' . $c['id'] . '">\s*<td>1</td>\s*<td>Extintor</td>#', $html);
        $this->assertMatchesRegularExpression('#<tr id="b' . $b['id'] . '">\s*<td>2</td>\s*<td>Pneus novos</td>#', $html);
        $this->assertStringContainsString("onsubmit=\"return confirm('Excluir este bem?')\"", $html);
        $this->assertStringContainsString('<th class="num">1.350,50</th>', $html);
    }

    public function testFirstItemSuggestsProjectObject(): void
    {
        // CA-B02 (DEC-B1)
        $form = $this->get("{$this->rb}/bens/novo")->body;
        $this->assertStringContainsString('>AQUISIÇÃO DE UM VEÍCULO ÔNIBUS</textarea>', $form);
        $this->assertStringContainsString('name="valor" required inputmode="decimal" placeholder="1.234,56" value=""', $form);
        $this->criar('Ônibus', '10,00');
        $this->assertStringContainsString('maxlength="500" autofocus></textarea>', $this->get("{$this->rb}/bens/novo")->body);
    }

    public function testPdfHtmlFollowsTheSpreadsheet(): void
    {
        // CA-B03: o HTML recebido pelo Dompdf
        $this->criar('Ônibus escolar', '497.152,49');
        $this->criar('Pneus', '1.000,00');
        $html = $this->htmlPdf();
        foreach (['MUNICÍPIO DE CAXAMBU DO SUL - SC', 'AQUISIÇÃO DE UM VEÍCULO ÔNIBUS', '<div class="titulo">RELAÇÃO DE BENS ADQUIRIDOS</div>',
                  '<td class="item">1</td>', '<td class="desc">Ônibus escolar</td>', '<td class="valor">497.152,49</td>',
                  '<td class="item">2</td>', '<td class="desc">TOTAL</td>', '<td class="valor">498.152,49</td>',
                  'Caxambu do Sul - SC, 05 de março de 2026', 'MAICON LUCAS LUFT', 'Contador - CRC/SC 102.316', 'EDI MARCOS'] as $parte) {
            $this->assertStringContainsString($parte, $html, $parte);
        }
        $this->assertSame(1, substr_count($html, '>TOTAL<'));
    }

    public function testEmptyListStillGeneratesPortraitPdf(): void
    {
        $this->assertStringContainsString('Nenhum bem cadastrado.', $this->htmlPdf());
        $this->assertStringContainsString('<td class="valor">0,00</td>', $this->htmlPdf());

        $this->post("{$this->rb}/gerar");
        $doc = $this->situacao09();
        $this->assertNotNull($doc, 'o documento 09 deve ficar registrado');
        $this->assertSame('09-relacao-de-bens.pdf', $doc['nome_original']);
        $bytes = (string) file_get_contents(FileStorage::default()->absolute($doc['caminho']));
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertMatchesRegularExpression('#/MediaBox \[0\.000 0\.000 595\.280 841\.890\]#', $bytes, 'A4 em retrato');
    }

    public function testTitleIsEditableAndEmptyRestoresDefault(): void
    {
        // CA-B04
        $this->post("{$this->rb}/titulo", ['titulo_documento' => '  RELAÇÃO DE BENS   CONSTRUÍDOS ']);
        $this->assertStringContainsString('value="RELAÇÃO DE BENS CONSTRUÍDOS"', $this->get($this->rb)->body);
        $this->assertStringContainsString('<div class="titulo">RELAÇÃO DE BENS CONSTRUÍDOS</div>', $this->htmlPdf());

        $this->post("{$this->rb}/titulo", ['titulo_documento' => '']);
        $this->assertStringContainsString('value="RELAÇÃO DE BENS ADQUIRIDOS"', $this->get($this->rb)->body);

        $this->post("{$this->rb}/titulo", ['titulo_documento' => str_repeat('A', 201)]);
        $this->assertStringContainsString('value="RELAÇÃO DE BENS ADQUIRIDOS"', $this->get($this->rb)->body, 'título longo demais é recusado');
    }

    public function testProjectPageShowsRow09(): void
    {
        // CA-B05
        $this->criar('Ônibus', '10,00');
        $page = $this->get("/projetos/{$this->pid}")->body;
        preg_match('#<li id="doc-09".*?</li>#s', $page, $m);
        $this->assertStringContainsString('<span class="tag aviso-tag">Não gerado</span>', $m[0]);
        $this->assertStringContainsString('1 bem(ns)', $m[0]);
        $this->assertStringContainsString("href=\"{$this->rb}\">Abrir relação de bens</a>", $m[0]);

        $this->post("{$this->rb}/gerar");
        preg_match('#<li id="doc-09".*?</li>#s', $this->get("/projetos/{$this->pid}")->body, $m);
        $this->assertStringContainsString('<span class="tag ok">Gerado</span>', $m[0]);
        $this->assertStringContainsString("href=\"/projetos/{$this->pid}/documentos/09/baixar\">Baixar</a>", $m[0]);
    }

    public function testChangesAfterGeneratingMarkOutdated(): void
    {
        // CA-B06
        $this->criar('A', '10,00');
        $this->criar('B', '20,00');
        $ids = array_column($this->bens(), 'id');
        $acoes = [
            fn () => $this->criar('C', '30,00'),
            fn () => $this->post("{$this->rb}/bens/{$ids[0]}", ['descricao' => 'A2', 'valor' => '11,00']),
            fn () => $this->post("{$this->rb}/bens/{$ids[1]}/mover", ['direcao' => 'cima']),
            fn () => $this->post("{$this->rb}/bens/{$ids[0]}/excluir"),
            fn () => $this->post("{$this->rb}/titulo", ['titulo_documento' => 'OUTRO']),
        ];
        foreach ($acoes as $i => $acao) {
            $this->post("{$this->rb}/gerar");
            $this->assertSame(0, (int) $this->situacao09()['desatualizado'], "ação $i: recém-gerado");
            $acao();
            $this->assertSame(1, (int) $this->situacao09()['desatualizado'], "ação $i deveria marcar desatualizado");
        }
        $this->assertStringContainsString('Os bens ou o título mudaram desde a última geração.', $this->get($this->rb)->body);
    }

    public function testValidation(): void
    {
        // CA-B07
        foreach (['' => 'Informe o valor do bem.', '0' => 'maior que zero', '-5' => 'Valor inválido', 'abc' => 'Valor inválido'] as $valor => $msg) {
            $_SESSION = [];
            $res = $this->criar('Ônibus', (string) $valor);
            $this->assertSame("{$this->rb}/bens/novo", $res->headers['Location'] ?? null, "valor «{$valor}»");
            $this->assertStringContainsString($msg, json_encode($_SESSION, JSON_UNESCAPED_UNICODE), "valor «{$valor}»");
        }
        $this->criar('   ', '10,00');
        $this->assertSame([], $this->bens());
        $this->assertStringContainsString('Informe a descrição do bem.', json_encode($_SESSION, JSON_UNESCAPED_UNICODE));
    }

    public function testFailedValidationKeepsTypedValues(): void
    {
        $this->criar('Meu bem', 'xyz');
        $form = $this->get("{$this->rb}/bens/novo")->body;
        $this->assertStringContainsString('>Meu bem</textarea>', $form);
        $this->assertStringContainsString('value="xyz"', $form);
    }

    public function testOtherProjectsItemIs404AndCsrfIsRequired(): void
    {
        $this->criar('A', '10,00');
        $bid = (int) $this->bens()[0]['id'];
        $pdo = Database::connection();
        $pdo->exec("INSERT INTO projetos (municipio_id, objeto) SELECT municipio_id, 'Outro' FROM projetos WHERE id = {$this->pid}");
        $outro = (int) $pdo->lastInsertId();
        $this->assertSame(404, $this->get("/projetos/$outro/relacao-de-bens/bens/$bid/editar")->status);
        $this->assertSame(404, $this->post("/projetos/$outro/relacao-de-bens/bens/$bid/excluir")->status);
        $this->assertSame(404, $this->get('/projetos/999999/relacao-de-bens')->status);
        $this->assertCount(1, $this->bens());

        $sem = handle(new Request('POST', "{$this->rb}/bens", [], ['descricao' => 'X', 'valor' => '1,00']), 'localhost');
        $this->assertNotSame(303, $sem->status);
        $this->assertCount(1, $this->bens());
    }

    public function testEscapingAndCascade(): void
    {
        $this->criar('<script>x</script>', '10,00');
        $this->assertStringNotContainsString('<script>x</script>', $this->get($this->rb)->body);
        $this->assertStringNotContainsString('<script>x</script>', $this->htmlPdf());
        $this->post("/projetos/{$this->pid}/excluir");
        $this->assertSame(0, (int) Database::connection()->query("SELECT COUNT(*) FROM bens WHERE projeto_id = {$this->pid}")->fetchColumn());
    }
}
