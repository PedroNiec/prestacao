<?php
declare(strict_types=1);

namespace Tests;

use App\Catalog\DocumentCatalog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

/** Tela do Projeto (specs/redesenho-tela-projeto.md, que refaz a fase 2.1): estrutura e critérios de aceitação. */
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
        preg_match_all('#<li id="doc-([0-9a-z]+)".*?</li>#s', $html, $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as [$li, $codigo]) {
            $out[$codigo] = $li;
        }
        return $out;
    }

    /** Remove todos os <details> (inclusive os aninhados) que começam com $abertura, ex.: '<details class="menu-mais">'. */
    private function semDetails(string $html, string $abertura = '<details'): string
    {
        while (($ini = strpos($html, $abertura)) !== false) {
            $prof = 0;
            $pos = $ini;
            while (preg_match('#<(/?)details\b#', $html, $m, PREG_OFFSET_CAPTURE, $pos)) {
                $prof += $m[1][0] === '/' ? -1 : 1;
                $pos = $m[0][1] + 1;
                if ($prof === 0) {
                    $fim = (int) strpos($html, '>', $m[0][1]) + 1;
                    $html = substr($html, 0, $ini) . substr($html, $fim);
                    continue 2;
                }
            }
            break;
        }
        return $html;
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
        $this->assertStringNotContainsString('type="file"', $this->semDetails($html));
        $this->assertDoesNotMatchRegularExpression('#<details[^>]*\sopen#', $html, 'nenhum painel nem menu abre sozinho');
    }

    public function testUploadFormsKeepTheirContract(): void
    {
        $tr = $this->linhas($this->pagina())['16'];  // sem arquivo: o painel de envio é aberto pelo botão principal
        $this->assertStringContainsString("action=\"/projetos/{$this->pid}/documentos/16/enviar\" enctype=\"multipart/form-data\"", $tr);
        $this->assertStringContainsString('name="arquivo" accept="image/png,.png" required aria-label="Arquivo PNG do documento 16"', $tr);
        $this->assertStringContainsString('<summary class="btn secundario pequeno">Enviar arquivo</summary>', $tr);
        $this->assertStringContainsString('Envie PNG; o sistema converte para PDF.', $tr);
    }

    public function testAtMostOneHighlightedActionPerRow(): void
    {
        // CA-T03: fora dos painéis e do menu ⋮, no máximo um botão por linha
        $this->enviar('18');
        $linhas = $this->linhas($this->pagina());
        $this->assertSame(array_column(DocumentCatalog::all(), 'codigo'), array_map('strval', array_keys($linhas)));
        foreach ($linhas as $codigo => $li) {
            $visivel = $this->semDetails($li, '<details class="menu-mais">');
            $visivel = (string) preg_replace('#<div class="recolhe-painel">.*?</div>#s', '', $visivel);
            $this->assertLessThanOrEqual(1, substr_count($visivel, 'class="btn'), "linha $codigo");
        }
    }

    public function testUploadWithFileHasViewAsMainActionAndSecondaryActionsInMenu(): void
    {
        $this->enviar('18');
        $li = $this->linhas($this->pagina())['18'];
        $this->assertStringContainsString('class="btn secundario pequeno" href="/projetos/' . $this->pid . '/documentos/18/baixar?ver=1" target="_blank" rel="noopener">Ver</a>', $li);
        $this->assertStringContainsString('<summary aria-label="Mais ações para o documento 18 — Parecer do Controle Interno">⋮</summary>', $li);
        $menu = substr($li, (int) strpos($li, '<div class="menu-mais-painel">'));
        $this->assertStringContainsString('>Baixar</a>', $menu);
        $this->assertStringContainsString('<summary class="menu-item">Substituir</summary>', $menu);
        $this->assertStringContainsString('action="/projetos/' . $this->pid . '/documentos/18/enviar" enctype="multipart/form-data"', $menu);
        $this->assertStringContainsString("onsubmit=\"return confirm('Remover este arquivo?')\"", $menu);
        $this->assertStringContainsString('>Remover</button>', $menu);
        $this->assertStringNotContainsString('Enviar arquivo', $li, 'com arquivo, a ação principal deixa de ser enviar');
    }

    public function testMenuAppearsOnlyWhenThereAreSecondaryActions(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO documentos (projeto_id, doc_codigo, caminho) VALUES (?, ?, ?)')->execute([$this->pid, '03', 'x.pdf']); // junção com PDF
        $linhas = $this->linhas($this->pagina());
        foreach (['01', '02', '09', '10b', '16'] as $semMenu) {   // formulário/junção sem PDF e uploads sem arquivo
            $this->assertStringNotContainsString('menu-mais', $linhas[$semMenu], "linha $semMenu não tem ação secundária");
        }
        $this->assertStringContainsString('>Gerenciar arquivos</a>', $linhas['03']);
        $this->assertStringContainsString('<summary aria-label="Mais ações para o documento 03 — Notas fiscais">⋮</summary>', $linhas['03']);
        $this->assertStringContainsString('/documentos/03/baixar">Baixar</a>', $linhas['03']);
        $this->assertStringNotContainsString('Remover', $linhas['03'], 'documento gerado não tem remover');
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

    public function testListIsASemanticListAndKeepsTexts(): void
    {
        $html = $this->pagina();
        $this->assertSame(25, preg_match_all('#<li id="doc-#', $html));
        $this->assertStringContainsString('<ul class="doc-lista">', $html);
        $this->assertStringNotContainsString('<table', $html);
        $this->assertStringNotContainsString('role="columnheader"', $html);
        $this->assertStringNotContainsString('Como é preenchido', $html);
        // DEC-P3 mantinha os textos provisórios; com a Etapa 9 (specs/relacao-de-bens.md, DEC-B2) eles deixaram de existir
        $this->assertStringNotContainsString('Disponível em etapa futura', $html);
        $this->assertStringNotContainsString('será habilitado na próxima etapa', $html);
        $this->assertStringContainsString("href=\"/projetos/{$this->pid}/relacao-de-bens\">Abrir relação de bens</a>", $html);
        $this->assertStringContainsString('<small class="doc-tipo">Junção de PDFs</small>', $html);
        $this->assertStringNotContainsString('resumo', strtolower($html), 'sem resumo "N de 25" nem barra de andamento (DEC-P1)');
    }

    public function testHeaderHasIdentityZipNoticeAndCompactDataStrip(): void
    {
        $html = $this->pagina();
        $cab = substr($html, (int) strpos($html, '<div class="barra projeto-cab">'), 2400);
        $this->assertStringContainsString('<span class="icone-pasta" aria-hidden="true"></span>', $cab);
        $this->assertStringContainsString('<p class="dica projeto-zip">', $cab);
        $this->assertStringContainsString('Nenhum documento com PDF ainda', $cab);
        $this->assertStringContainsString('<dl class="dados-faixa">', $html);
        $this->assertStringNotContainsString('dados-grade', $html);
        $this->assertSame(8, substr_count($html, '<dt>'), 'Concedente, Processo, PP, NE, NL, Local e data, Contador, Prefeito');
    }

    public function testZipNoticeShowsPendingAndKeepsConfirmation(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO documentos (projeto_id, doc_codigo, caminho, desatualizado) VALUES (?, ?, ?, 1)')->execute([$this->pid, '03', 'x.pdf']);
        $html = $this->pagina();
        $this->assertStringContainsString('1 documento(s) no ZIP', $html);
        $this->assertStringContainsString('<span class="tag aviso-tag">1 desatualizado(s)</span>', $html);
        $this->assertStringContainsString('onclick="return confirm(', $html);
    }

    public function testOutdatedRowIsMarked(): void
    {
        Database::connection()->prepare('INSERT INTO documentos (projeto_id, doc_codigo, caminho, desatualizado) VALUES (?, ?, ?, 1)')
            ->execute([$this->pid, '03', 'x.pdf']);
        $linhas = $this->linhas($this->pagina());
        $this->assertStringContainsString('class="doc-item doc-pendente"', $linhas['03']);
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
