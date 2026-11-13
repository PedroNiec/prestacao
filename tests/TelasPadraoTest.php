<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

/** Padrão visual das demais telas (specs/melhoria-visual-demais-telas.md, CA-T01 a CA-T05). */
final class TelasPadraoTest extends TestCase
{
    private int $pid;
    private int $mid;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Caxambu do Sul', 'SC')");
        $this->mid = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO projetos (municipio_id, objeto) VALUES (?, ?)')
            ->execute([$this->mid, 'Ônibus <script>x</script> escolar']);
        $this->pid = (int) $pdo->lastInsertId();
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    private function receita(int $centavos): void
    {
        Database::connection()->prepare("INSERT INTO lancamentos (projeto_id, ordem, descricao, origem, fonte, tipo, valor_centavos) VALUES (?, 1, 'Repasse', 'convenio', 1, 'receita', ?)")
            ->execute([$this->pid, $centavos]);
    }

    private function receitaConciliacao(int $centavos): void
    {
        Database::connection()->prepare("INSERT INTO conciliacao_receitas (projeto_id, ordem, tipo, descricao, valor_centavos) VALUES (?, 1, 'repasse', '', ?)")
            ->execute([$this->pid, $centavos]);
    }

    /** Cria um lançamento e devolve o id (a página de lançamento agora é só de edição; specs/balancete-lancamento-na-tela.md). */
    private function lancamento(): int
    {
        $pdo = Database::connection();
        $pdo->prepare("INSERT INTO lancamentos (projeto_id, ordem, descricao, origem, fonte, tipo, valor_centavos) VALUES (?, 1, 'X', 'convenio', 1, 'despesa', 100)")
            ->execute([$this->pid]);
        return (int) $pdo->lastInsertId();
    }

    /** @return array<string,string> tela => URL (todas as telas internas) */
    private function telasInternas(): array
    {
        $p = "/projetos/{$this->pid}";
        return [
            'novo projeto' => '/projetos/novo', 'editar projeto' => "$p/editar",
            'novo município' => '/municipios/novo', 'editar município' => "/municipios/{$this->mid}/editar",
            'balancete' => "$p/balancete", 'lançamento' => "$p/balancete/lancamentos/{$this->lancamento()}/editar",
            'físico-financeiro' => "$p/fisico-financeiro", 'meta' => "$p/fisico-financeiro/linhas/novo",
            'conciliação' => "$p/conciliacao", 'nota fiscal' => "$p/conciliacao/notas/novo",
            'junção' => "$p/documentos/03",
        ];
    }

    public function testEveryInternalScreenHasBreadcrumbAndNoBackLink(): void
    {
        // CA-T01
        foreach ($this->telasInternas() as $tela => $url) {
            $res = $this->get($url);
            $this->assertSame(200, $res->status, $tela);
            $this->assertStringContainsString('<nav aria-label="Trilha de navegação">', $res->body, $tela);
            $this->assertMatchesRegularExpression('#<li><a href="/(projetos|municipios)">(Projetos|Municípios)</a></li>#u', $res->body, $tela);
            $this->assertStringNotContainsString('← Voltar', $res->body, $tela);
            $this->assertStringNotContainsString('<script>x</script>', $res->body, $tela);
        }
    }

    public function testBreadcrumbShowsEscapedObjectAndLinksBackToDocumentRow(): void
    {
        $html = $this->get("/projetos/{$this->pid}/balancete/lancamentos/{$this->lancamento()}/editar")->body;
        $obj = 'Ônibus &lt;script&gt;x&lt;/script&gt; escolar';
        $this->assertStringContainsString("<a href=\"/projetos/{$this->pid}#doc-01\" class=\"trilha-objeto\" title=\"$obj\">$obj</a>", $html);
        $this->assertStringContainsString("<a href=\"/projetos/{$this->pid}/balancete\">01 Balancete</a>", $html);
        $this->assertStringContainsString('<li aria-current="page"><span>Editar lançamento</span></li>', $html);
    }

    public function testMunicipioScreensUseMunicipiosBreadcrumb(): void
    {
        $html = $this->get('/municipios/novo')->body;
        $this->assertStringContainsString('<li><a href="/municipios">Municípios</a></li>', $html);
        $this->assertStringContainsString('<li aria-current="page"><span>Novo município</span></li>', $html);
        $this->assertStringContainsString('class="campo-uf"', $html);
    }

    public function testDocumentScreensShowTheSamePdfCard(): void
    {
        // CA-T02
        $p = "/projetos/{$this->pid}";
        foreach (["$p/balancete" => 'PDF não gerado', "$p/fisico-financeiro" => 'PDF não gerado',
                  "$p/conciliacao" => 'PDF não gerado', "$p/documentos/03" => 'Não gerado'] as $url => $etiqueta) {
            $html = $this->get($url)->body;
            $this->assertStringContainsString('<section class="cartao cartao-pdf"', $html, $url);
            $this->assertStringContainsString("<span class=\"tag vazio\">$etiqueta</span>", $html, $url);
            $this->assertStringContainsString('>Gerar PDF</button>', $html, $url);
        }
        // junção sem arquivos: botão desabilitado, como antes
        $this->assertStringContainsString('type="submit" disabled>Gerar PDF', $this->get("$p/documentos/03")->body);
    }

    public function testOutdatedPdfCardKeepsItsText(): void
    {
        Database::connection()->prepare('INSERT INTO documentos (projeto_id, doc_codigo, caminho, desatualizado) VALUES (?, ?, ?, 1)')
            ->execute([$this->pid, '01', 'x.pdf']);
        $html = $this->get("/projetos/{$this->pid}/balancete")->body;
        $this->assertStringContainsString('<span class="tag aviso-tag">Desatualizado</span> Os lançamentos mudaram desde a última geração.', $html);
        $this->assertStringContainsString("href=\"/projetos/{$this->pid}/documentos/01/baixar?ver=1\"", $html);
    }

    public function testEmptyListsUseNeutralStateInsteadOfWarning(): void
    {
        // CA-T03
        $p = "/projetos/{$this->pid}";
        foreach (["$p/balancete", "$p/fisico-financeiro", "$p/documentos/03"] as $url) {
            $html = $this->get($url)->body;
            $this->assertStringContainsString('class="estado-vazio"', $html, $url);
            $this->assertStringNotContainsString('class="aviso"', $html, $url);
        }
        $html = $this->get("$p/conciliacao")->body;
        $this->assertStringContainsString('<strong>Nenhuma nota fiscal.</strong>', $html);
        Database::connection()->exec('DELETE FROM projetos');
        // Fase 3.2: a lista de projetos usa a variante com ícone (estado-vazio estado-vazio--pasta)
        $this->assertMatchesRegularExpression('#class="estado-vazio( [a-z-]+)?"#', $this->get('/projetos')->body);
    }

    public function testNoInlineStylesInScreenTemplates(): void
    {
        // CA-T04
        $raiz = dirname(__DIR__) . '/templates';
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
        $com = [];
        foreach ($it as $f) {
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($raiz) + 1));
            if (str_ends_with($rel, '.php') && !str_starts_with($rel, 'pdf/') && str_contains((string) file_get_contents($f->getPathname()), 'style="')) {
                $com[] = $rel;
            }
        }
        $this->assertSame([], $com);
    }

    public function testBalanceteShowsIndicators(): void
    {
        // CA-T05
        $this->receita(100050);
        $html = $this->get("/projetos/{$this->pid}/balancete")->body;
        $this->assertSame(3, substr_count($html, '<div class="kpi">'));
        $this->assertStringContainsString('<span class="kpi-rotulo">Receita total</span><span class="kpi-valor">1.000,50</span>', $html);
    }

    public function testConciliacaoIndicatorSaysWhetherItClosed(): void
    {
        // CA-T05: vazia = diferença zero = fechou
        $html = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertSame(5, preg_match_all('#<div class="kpi( ok| erro)?">#', $html));
        $this->assertStringContainsString('<div class="kpi ok">', $html);
        $this->assertStringContainsString('<span class="kpi-nota">Fechou</span>', $html);
        $this->assertStringNotContainsString('class="aviso"', $html);

        $this->receitaConciliacao(50000);
        $html = $this->get("/projetos/{$this->pid}/conciliacao")->body;
        $this->assertStringContainsString('<div class="kpi erro">', $html);
        $this->assertStringContainsString('<span class="kpi-nota">Não fechou</span>', $html);
        $this->assertStringContainsString('<p class="aviso">A conciliação <strong>não fechou</strong>', $html);
        foreach (['href="#receitas"', 'id="receitas"', 'id="notas"', 'id="dados"'] as $ancora) {
            $this->assertStringContainsString($ancora, $html);
        }
    }

    public function testScriptsHooksArePreserved(): void
    {
        $p = "/projetos/{$this->pid}";
        $form = $this->get('/projetos/novo')->body;
        foreach (['id="form-projeto"', 'id="municipio_id"', 'id="local"', 'id="prefeito_nome"', 'id="contador_nome"', 'id="contador_crc"', 'src="/assets/projeto-form.js"'] as $h) {
            $this->assertStringContainsString($h, $form, $h);
        }
        // o formulário de novo lançamento fica na tela do balancete (o "Salvar e adicionar outro" saiu: DEC-BL1)
        $lanc = $this->get("$p/balancete")->body;
        foreach (['id="form-lancamento"', 'id="origem"', 'id="fonte"', 'src="/assets/lancamento-form.js"'] as $h) {
            $this->assertStringContainsString($h, $lanc, $h);
        }
        $st = Database::connection()->prepare("INSERT INTO arquivos (projeto_id, doc_codigo, ordem, nome_original, caminho, tamanho) VALUES (?, '03', ?, ?, 'x', 10)");
        $st->execute([$this->pid, 1, 'a.pdf']);
        $st->execute([$this->pid, 2, 'b.pdf']);
        $jun = $this->get("$p/documentos/03")->body;
        foreach (['id="form-ordem"', 'id="lista"', 'data-arrastavel="1"', 'src="/assets/juncao.js"'] as $h) {
            $this->assertStringContainsString($h, $jun, $h);
        }
        $this->assertSame(2, substr_count($jun, 'draggable="true"'));
    }

    public function testListsShowObjectAsLinkAndMunicipalityCards(): void
    {
        $lista = $this->get('/projetos')->body;
        // Fase 3.2 (redesenho-lista-projetos): o link do objeto passou a ser "esticado" sobre o cartão (classe projeto-link)
        $this->assertStringContainsString("<a class=\"projeto-link\" href=\"/projetos/{$this->pid}\">Ônibus &lt;script&gt;x&lt;/script&gt; escolar</a>", $lista);
        $mun = $this->get('/municipios')->body;
        // Fase 3.1 (redesenho-layout-municipios, RF-M02): a contagem passou da etiqueta para o subtítulo do cartão.
        $this->assertStringContainsString('<span class="item-sub">SC · 1 projeto(s)</span>', $mun);
        // DEC-L2: com projetos, "Excluir" aparece desabilitado (a confirmação é testada em MunicipiosTelaTest)
        $this->assertStringContainsString('<button class="link perigo" type="button" disabled>Excluir</button>', $mun);
    }

    public function testFormsHaveCancelAsSecondaryButton(): void
    {
        foreach (['/projetos/novo', '/municipios/novo', "/projetos/{$this->pid}/balancete/lancamentos/{$this->lancamento()}/editar",
                  "/projetos/{$this->pid}/fisico-financeiro/linhas/novo", "/projetos/{$this->pid}/conciliacao/notas/novo"] as $url) {
            $this->assertMatchesRegularExpression('#<a class="btn secundario" href="[^"]+">Cancelar</a>#', $this->get($url)->body, $url);
        }
    }
}
