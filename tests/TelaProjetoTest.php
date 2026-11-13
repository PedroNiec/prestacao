<?php
declare(strict_types=1);

namespace Tests;

use App\Catalog\DocumentCatalog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

/** Tela do Projeto (specs/melhoria-visual-tela-projeto.md): estrutura e critérios de aceitação. */
final class TelaProjetoTest extends TestCase
{
    private int $pid;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM projetos');
        $pdo->exec('DELETE FROM municipios');
        $_SESSION = [];
        $pdo->exec("INSERT INTO municipios (nome, uf) VALUES ('Caxambu do Sul', 'SC')");
        $mid = (int) $pdo->lastInsertId();
        $st = $pdo->prepare('INSERT INTO projetos (municipio_id, objeto, nr_pp, contador_nome, contador_crc) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$mid, 'Aquisição de ônibus <script>x</script>', '2025PP052544', 'Maicon', 'CRC/SC 1']);
        $this->pid = (int) $pdo->lastInsertId();
    }

    private function get(string $path): Response
    {
        return handle(new Request('GET', $path), 'localhost');
    }

    private function pagina(): string
    {
        return $this->get("/projetos/{$this->pid}")->body;
    }

    private function enviar(string $codigo): void
    {
        handle(new Request('POST', "/projetos/{$this->pid}/documentos/$codigo/enviar", [], ['_csrf' => Csrf::token()],
            ['arquivo' => Support::pdf('parecer.pdf')]), 'localhost');
    }

    /** @return array<string,string> HTML de cada linha, por código */
    private function linhas(string $html): array
    {
        preg_match_all('#<tr id="doc-([0-9a-z]+)".*?</tr>#s', $html, $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as [$tr, $codigo]) {
            $out[$codigo] = $tr;
        }
        return $out;
    }

    public function testHeaderShowsObjectMunicipalityAndBreadcrumb(): void
    {
        $html = $this->pagina();
        $this->assertMatchesRegularExpression('#<h1>Aquisição de ônibus &lt;script&gt;x&lt;/script&gt;</h1>#u', $html);
        $this->assertStringNotContainsString('<script>x</script>', $html);
        $this->assertStringContainsString("Caxambu do Sul - SC · Projeto #{$this->pid}", $html);
        $this->assertStringContainsString("<title>Projeto #{$this->pid} — Prestação de Contas</title>", $html);
        $this->assertStringContainsString('<li><a href="/projetos">Projetos</a></li>', $html);
        $this->assertStringContainsString("<li aria-current=\"page\">Projeto #{$this->pid}</li>", $html);
        $this->assertStringContainsString("href=\"/projetos/{$this->pid}/editar\"", $html);
    }

    public function testProjectDataCardShowsEachFieldWithDashWhenEmpty(): void
    {
        $html = $this->pagina();
        $this->assertStringContainsString('<dt>PP</dt><dd>2025PP052544</dd>', $html);
        $this->assertStringContainsString('<dt>NE</dt><dd>—</dd>', $html);
        $this->assertStringContainsString('<dt>Contador</dt><dd>Maicon <small>(CRC/SC 1)</small></dd>', $html);
        $this->assertStringContainsString('<dt>Prefeito</dt><dd>—</dd>', $html);
    }

    public function testNoFileInputIsVisibleOnLoad(): void
    {
        // CA-P01: todo campo de arquivo fica dentro de um <details> fechado
        $html = $this->pagina();
        $this->assertSame(13, substr_count($html, 'type="file"'));
        $semDetails = preg_replace('#<details class="recolhe">.*?</details>#s', '', $html);
        $this->assertStringNotContainsString('type="file"', (string) $semDetails);
        $this->assertStringNotContainsString('<details class="recolhe" open', $html);
    }

    public function testUploadFormsKeepTheirContract(): void
    {
        $tr = $this->linhas($this->pagina())['16'];
        $this->assertStringContainsString("action=\"/projetos/{$this->pid}/documentos/16/enviar\" enctype=\"multipart/form-data\"", $tr);
        $this->assertStringContainsString('name="arquivo" accept="image/png,.png" required aria-label="Arquivo PNG do documento 16"', $tr);
        $this->assertStringContainsString('<summary class="btn secundario pequeno">Enviar arquivo</summary>', $tr);
        $this->assertStringContainsString('Envie PNG; o sistema converte para PDF.', $tr);
    }

    public function testAtMostOneHighlightedActionPerRow(): void
    {
        // CA-P02: fora do painel recolhido, no máximo um botão por linha
        $this->enviar('18');
        $linhas = $this->linhas($this->pagina());
        $this->assertSame(array_column(DocumentCatalog::all(), 'codigo'), array_map('strval', array_keys($linhas)));
        foreach ($linhas as $codigo => $tr) {
            $visivel = (string) preg_replace('#<div class="recolhe-painel">.*?</div>#s', '', $tr);
            $this->assertLessThanOrEqual(1, substr_count($visivel, 'class="btn'), "linha $codigo");
        }
        $tr = $linhas['18'];
        $this->assertStringContainsString('>Ver</a>', $tr);
        $this->assertStringContainsString('>Baixar</a>', $tr);
        $this->assertStringContainsString('<summary class="btn secundario pequeno">Substituir</summary>', $tr);
        $this->assertStringContainsString("onsubmit=\"return confirm('Remover este arquivo?')\"", $tr);
    }

    public function testDeleteOnlyInDangerZoneWithConfirmation(): void
    {
        // CA-P03
        $html = $this->pagina();
        $this->assertSame(1, substr_count($html, "/projetos/{$this->pid}/excluir"));
        $this->assertMatchesRegularExpression(
            "#<section class=\"zona-perigo\".*action=\"/projetos/{$this->pid}/excluir\" onsubmit=\"return confirm\('Excluir este projeto e todos os seus documentos\?'\)\".*>Excluir projeto</button>.*</section>#s",
            $html
        );
    }

    public function testListHasFourColumnsAndKeepsTexts(): void
    {
        $html = $this->pagina();
        $this->assertSame(25, preg_match_all('#<tr id="doc-#', $html));
        $this->assertStringNotContainsString('Como é preenchido', $html);
        $this->assertSame(4, substr_count($html, 'role="columnheader"'));
        // DEC-P3 mantinha os textos provisórios; com a Etapa 9 (specs/relacao-de-bens.md, DEC-B2) eles deixaram de existir
        $this->assertStringNotContainsString('Disponível em etapa futura', $html);
        $this->assertStringNotContainsString('será habilitado na próxima etapa', $html);
        $this->assertStringContainsString("href=\"/projetos/{$this->pid}/relacao-de-bens\">Abrir relação de bens</a>", $html);
        $this->assertStringContainsString('<small class="doc-tipo">Junção de PDFs</small>', $html);
    }

    public function testOutdatedRowIsMarked(): void
    {
        Database::connection()->prepare('INSERT INTO documentos (projeto_id, doc_codigo, caminho, desatualizado) VALUES (?, ?, ?, 1)')
            ->execute([$this->pid, '03', 'x.pdf']);
        $linhas = $this->linhas($this->pagina());
        $this->assertStringContainsString('class="doc-linha doc-pendente"', $linhas['03']);
        $this->assertStringContainsString('<span class="tag aviso-tag">Desatualizado</span>', $linhas['03']);
        $this->assertStringNotContainsString('doc-pendente', $linhas['02']);
    }

    public function testEveryPostFormHasCsrfToken(): void
    {
        $this->enviar('18');
        $html = $this->pagina();
        preg_match_all('#<form method="post".*?</form>#s', $html, $m);
        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $form) {
            $this->assertStringContainsString('name="_csrf"', $form);
        }
    }
}
