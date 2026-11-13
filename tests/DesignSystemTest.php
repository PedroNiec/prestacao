<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

/**
 * Guardas automáticas da fundação visual (specs/melhoria-visual-fundacao.md).
 * Não enxergam layout: garantem contraste dos tokens, consistência das classes, tamanho,
 * funcionamento offline, estrutura do layout e que os PDFs não foram alterados.
 */
final class DesignSystemTest extends TestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        $this->raiz = dirname(__DIR__);
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    // ---------- utilidades ----------

    private function css(): string
    {
        return (string) file_get_contents($this->raiz . '/public/assets/app.css');
    }

    /** @return array<string,string> token => #rrggbb */
    private function tokens(): array
    {
        preg_match('/:root\s*\{(.*?)\n\}/s', $this->css(), $bloco);
        preg_match_all('/--([a-z0-9-]+)\s*:\s*(#[0-9a-fA-F]{6})\b/', $bloco[1] ?? '', $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as [, $nome, $hex]) {
            $out[$nome] = strtolower($hex);
        }
        return $out;
    }

    private static function luminancia(string $hex): float
    {
        $h = ltrim($hex, '#');
        $c = array_map(fn ($i) => hexdec(substr($h, $i, 2)) / 255, [0, 2, 4]);
        $c = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private static function contraste(string $a, string $b): float
    {
        [$la, $lb] = [self::luminancia($a), self::luminancia($b)];
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private function get(string $path): \App\Core\Response
    {
        $_SERVER['REQUEST_URI'] = $path;
        return handle(new Request('GET', $path), 'localhost');
    }

    // ---------- contraste (CA-V02) ----------

    /** @return array<string,array{0:string,1:string,2:float}> */
    public static function paresDeContraste(): array
    {
        $b = '#ffffff';
        return [
            'texto / fundo' => ['texto', 'fundo', 4.5], 'texto / superfície' => ['texto', 'superficie', 4.5],
            'texto suave / fundo' => ['texto-suave', 'fundo', 4.5], 'texto suave / superfície' => ['texto-suave', 'superficie', 4.5],
            'botão primário' => [$b, 'cor-primaria', 4.5], 'botão primário (hover)' => [$b, 'cor-primaria-hover', 4.5],
            'link / fundo' => ['cor-primaria', 'fundo', 4.5], 'link / superfície' => ['cor-primaria', 'superficie', 4.5],
            'link / primária suave (botão secundário hover)' => ['cor-primaria-hover', 'cor-primaria-suave', 4.5],
            'sucesso' => ['sucesso-fg', 'sucesso-bg', 4.5], 'aviso' => ['aviso-fg', 'aviso-bg', 4.5],
            'perigo' => ['perigo-fg', 'perigo-bg', 4.5], 'informação' => ['info-fg', 'info-bg', 4.5], 'neutro' => ['neutro-fg', 'neutro-bg', 4.5],
            'botão perigo' => [$b, 'perigo-solido', 4.5], 'botão perigo (hover)' => [$b, 'perigo-hover', 4.5],
            'ação discreta perigo / superfície' => ['perigo-solido', 'superficie', 4.5], 'ação discreta perigo (hover)' => ['perigo-hover', 'perigo-bg', 4.5],
            'texto / cabeçalho de tabela' => ['texto', 'tabela-cab', 4.5], 'texto / linha em hover' => ['texto', 'tabela-hover', 4.5],
            'texto / total de tabela' => ['texto', 'tabela-total', 4.5], 'texto / aviso legado' => ['texto', 'aviso-bg', 4.5],
            'borda de campo / fundo' => ['borda-campo', 'fundo', 3.0], 'borda de campo / superfície' => ['borda-campo', 'superficie', 3.0],
            'anel de foco / fundo' => ['foco', 'fundo', 3.0], 'anel de foco / superfície' => ['foco', 'superficie', 3.0],
            'cabeçalho: branco / primária' => [$b, 'cor-primaria', 4.5],
        ];
    }

    /** @dataProvider paresDeContraste */
    #[\PHPUnit\Framework\Attributes\DataProvider('paresDeContraste')]
    public function testTokenContrast(string $fg, string $bg, float $minimo): void
    {
        $t = $this->tokens();
        $cor = fn (string $x) => str_starts_with($x, '#') ? $x : ($t[$x] ?? $this->fail("token --$x não definido em :root"));
        $c = self::contraste($cor($fg), $cor($bg));
        $this->assertGreaterThanOrEqual($minimo, $c, sprintf('%s sobre %s = %.2f:1 (mínimo %.1f)', $fg, $bg, $c, $minimo));
    }

    public function testRequiredTokensExist(): void
    {
        $css = $this->css();
        foreach (['--esp-1', '--esp-4', '--esp-7', '--raio-1', '--raio-2', '--sombra-1', '--sombra-2', '--fs-base', '--fs-2xl', '--larg', '--larg-ampla', '--fonte'] as $t) {
            $this->assertStringContainsString($t . ':', $css, "token $t ausente");
        }
    }

    // ---------- consistência das classes (CA-V06) ----------

    /** @return string[] classes definidas no CSS */
    private function classesDefinidas(): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $this->css()) ?? '';
        preg_match_all('/\.([a-zA-Z][\w-]*)/', $css, $m);
        return array_values(array_unique($m[1]));
    }

    /** @return array<string,string[]> classe usada => arquivos */
    private function classesUsadasNosTemplates(): array
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->raiz . '/templates', \FilesystemIterator::SKIP_DOTS));
        $usadas = [];
        foreach ($it as $f) {
            // DT-2 (plans/relacao-de-bens-plan.md): no Windows o caminho vem com "\"; sem normalizar, pdf/ não era excluído
            $rel = str_replace(str_replace('\\', '/', $this->raiz) . '/templates/', '', str_replace('\\', '/', $f->getPathname()));
            if (!str_ends_with($rel, '.php') || str_starts_with($rel, 'pdf/')) {
                continue;
            }
            $html = (string) file_get_contents($f->getPathname());
            preg_match_all('/class="([^"]*)"/', $html, $m);
            foreach ($m[1] as $valor) {
                $limpo = preg_replace('/<\?(?:php|=).*?\?>/s', ' ', $valor) ?? '';
                $dinamico = str_contains($valor, '<?');
                foreach (preg_split('/\s+/', trim($limpo)) ?: [] as $classe) {
                    if ($classe !== '') {
                        $usadas[$classe . ($dinamico && str_ends_with($classe, '-') ? '*' : '')][] = $rel;
                    }
                }
            }
        }
        return $usadas;
    }

    public function testEveryClassUsedInTemplatesIsDefinedInCss(): void
    {
        $definidas = $this->classesDefinidas();
        $faltando = [];
        foreach ($this->classesUsadasNosTemplates() as $classe => $arquivos) {
            $ok = str_ends_with($classe, '*')
                ? array_filter($definidas, fn ($d) => str_starts_with($d, rtrim($classe, '*')))   // sufixo dinâmico (ex.: msg-ok/msg-erro)
                : in_array($classe, $definidas, true);
            if (!$ok) {
                $faltando[] = $classe . ' (' . implode(', ', array_unique($arquivos)) . ')';
            }
        }
        $this->assertSame([], $faltando, 'classes usadas nos templates sem definição no CSS');
    }

    public function testLegacyClassesRemainDefined(): void
    {
        // Compatibilidade (RF-V00): nomes herdados das etapas anteriores não podem sumir.
        $legado = ['btn', 'secundario', 'perigo', 'link', 'tag', 'vazio', 'ok', 'aviso-tag', 'form', 'caixa', 'dados', 'aviso', 'msg', 'msg-ok', 'msg-erro',
                   'rolagem', 'num', 'acoes', 'linha', 'botoes', 'dica', 'arq', 'breve', 'envio', 'lista-arquivos', 'arrastando', 'nome', 'alca',
                   'compacta', 'form-larga', 'inline', 'barra', 'conteudo', 'topo', 'marca'];
        $this->assertSame([], array_values(array_diff($legado, $this->classesDefinidas())));
    }

    // ---------- integridade do CSS (o navegador falha em silêncio) ----------

    public function testEveryVarReferenceHasADefinition(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->css());
        preg_match_all('/--([a-z0-9-]+)\s*:/', $css, $def);
        preg_match_all('/var\(--([a-z0-9-]+)/', $css, $uso);
        $this->assertSame([], array_values(array_diff(array_unique($uso[1]), $def[1])), 'var(--x) sem definição: o navegador ignoraria a regra inteira');
    }

    public function testCssBracesAndParenthesesAreBalanced(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->css());
        $this->assertSame(substr_count($css, '{'), substr_count($css, '}'), 'chaves desbalanceadas');
        $this->assertSame(substr_count($css, '('), substr_count($css, ')'), 'parênteses desbalanceados');
    }

    public function testCssHasNoImportantOutsideReducedMotion(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->css());
        $fora = preg_replace('/@media \(prefers-reduced-motion: reduce\)\s*\{.*?\n\}/s', '', $css) ?? '';
        $this->assertStringNotContainsString('!important', $fora, '!important só é aceitável na regra de movimento reduzido');
    }

    // ---------- tamanho e offline (CA-V09) ----------

    public function testSizeLimits(): void
    {
        // Limite elevado de 30 para 40 KB na DEC-L3 (specs/redesenho-layout-municipios.md): sistema local, sem custo de rede.
        $this->assertLessThanOrEqual(40 * 1024, filesize($this->raiz . '/public/assets/app.css'), 'CSS acima de 40 KB');
        $this->assertLessThanOrEqual(3 * 1024, filesize($this->raiz . '/public/assets/app.js'), 'JS global acima de 3 KB');
    }

    public function testNoExternalResources(): void
    {
        $arquivos = ['public/assets/app.css', 'public/assets/app.js', 'public/assets/favicon.svg', 'templates/layout.php', 'templates/erro.php', 'templates/guia_visual.php'];
        foreach ($arquivos as $a) {
            preg_match_all('#https?://[^\s"\')<>]+#', (string) file_get_contents($this->raiz . '/' . $a), $m);
            foreach ($m[0] as $url) {
                $this->assertSame('http://www.w3.org/2000/svg', $url, "$a referencia recurso externo: $url");
            }
        }
        $this->assertStringNotContainsString('@import', $this->css());
        $this->assertStringNotContainsString('@font-face', $this->css());
    }

    // ---------- layout global (CA-V04/V05/V07) ----------

    public function testLayoutStructureAndAccessibility(): void
    {
        $body = $this->get('/projetos')->body;
        $this->assertStringContainsString('<html lang="pt-BR">', $body);
        $this->assertStringContainsString('class="pular-link" href="#conteudo"', $body);
        $this->assertStringContainsString('id="conteudo"', $body);
        $this->assertStringContainsString('rel="icon" type="image/svg+xml" href="/assets/favicon.svg"', $body);
        $this->assertStringContainsString('src="/assets/app.js" defer', $body);
        $this->assertStringContainsString('aria-label="Principal"', $body);
    }

    public function testNavigationMarksCurrentSection(): void
    {
        // renderiza o layout diretamente para cada caminho (evita depender de rotas existentes)
        foreach (['/projetos' => 'Projetos', '/projetos/12/balancete' => 'Projetos', '/' => 'Projetos', '/municipios' => 'Municípios', '/municipios/3/editar' => 'Municípios'] as $path => $esperado) {
            $_SERVER['REQUEST_URI'] = $path;
            $html = \App\Core\View::render('erro', ['titulo' => 'X', 'mensagem' => 'Y']);
            preg_match_all('#<a href="[^"]+" aria-current="page">([^<]+)</a>#', $html, $m);
            $this->assertSame([$esperado], $m[1], "item ativo para $path");
        }
    }

    public function testMessagesAreStickyRegionWithCloseButton(): void
    {
        Session::flash('Salvo com sucesso.', 'ok');
        Session::flash('Algo deu errado.', 'erro');
        $body = $this->get('/projetos')->body;
        $this->assertStringContainsString('class="alertas" role="region" aria-label="Mensagens"', $body);
        $this->assertStringContainsString('class="msg msg-ok" role="status"', $body);
        $this->assertStringContainsString('class="msg msg-erro" role="alert"', $body);
        $this->assertSame(2, substr_count($body, 'class="msg-fechar"'));
        $this->assertMatchesRegularExpression('/\.alertas\s*\{[^}]*position:\s*sticky/', $this->css(), 'região fixa no topo da janela');
    }

    public function testNoMessageRegionWhenThereAreNoMessages(): void
    {
        $this->assertStringNotContainsString('class="alertas"', $this->get('/projetos')->body);
    }

    public function testMessageTextIsEscaped(): void
    {
        Session::flash('<script>alert(1)</script>', 'erro');
        $body = $this->get('/projetos')->body;
        $this->assertStringNotContainsString('<script>alert(1)', $body);
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $body);
    }

    public function testCloseScriptTargetsMessageComponents(): void
    {
        $js = (string) file_get_contents($this->raiz . '/public/assets/app.js');
        $this->assertStringContainsString('.msg-fechar', $js);
        $this->assertStringContainsString('alertas', $js);
        $this->assertStringNotContainsString('eval(', $js);
    }

    public function testErrorPagesKeepStatusAndUseNewState(): void
    {
        $res = $this->get('/rota-que-nao-existe');
        $this->assertSame(404, $res->status);
        $this->assertStringContainsString('class="estado-erro"', $res->body);
        $this->assertStringContainsString('Voltar ao início', $res->body);
        $this->assertSame(403, handle(new Request('GET', '/projetos'), 'evil.example.com')->status);
        $this->assertSame(419, handle(new Request('POST', '/municipios', [], []), 'localhost')->status);
    }

    // ---------- guia visual ----------

    public function testVisualGuideListsEveryComponent(): void
    {
        $res = $this->get('/guia-visual');
        $this->assertSame(200, $res->status);
        foreach (['btn secundario', 'btn perigo', 'btn pequeno', 'button class="link"', 'link perigo', 'class="form"', 'type="radio"', 'type="file"', 'select',
                  'textarea', 'class="rolagem"', 'class="num"', 'tfoot', 'tag ok', 'tag aviso-tag', 'tag erro', 'tag info', 'msg msg-ok', 'msg msg-erro',
                  'msg msg-aviso', 'msg msg-info', 'estado-vazio', 'class="kpi', 'class="caixa"', 'class="dados"', 'class="migalhas"', 'class="contexto"',
                  'lista-arquivos', 'disabled'] as $parte) {
            $this->assertStringContainsString($parte, $res->body, "guia sem: $parte");
        }
    }

    public function testVisualGuideIsStaticAndHasNoSubmittableForm(): void
    {
        $body = $this->get('/guia-visual')->body;
        $this->assertStringNotContainsString('<form', $body, 'o guia não envia dados');
        $this->assertStringNotContainsString('_csrf', $body);
        $this->assertSame(405, handle(new Request('POST', '/guia-visual', [], ['_csrf' => \App\Core\Csrf::token()]), 'localhost')->status);
    }

    public function testVisualGuideIsLinkedFromFooter(): void
    {
        $this->assertStringContainsString('href="/guia-visual"', $this->get('/projetos')->body);
    }

    // ---------- PDFs intactos (CA-V08) ----------

    public function testPdfTemplatesWereNotChangedByTheVisualRefactor(): void
    {
        // Linha de base registrada antes da fundação visual. Se os PDFs forem alterados de propósito
        // em outra melhoria, atualize estes hashes.
        $esperado = [
            'balancete.php' => '859125ca38e3d2ad9a66a9b9658c305b413d3cc5d67a6bd4d5aa6004570b26f5',
            '_cabecalho.php' => 'de4faebed6341171e57d6d284c753743fd8433697b254957083d37026844331a',
            'conciliacao.php' => 'bc46cdde35722b533b2bb323e42ec1900b30e2beb3b730ada5d527d6e879fa68',
            '_estilo.php' => 'bc97713000c708d9f040bc83ab0dcbbee81a2bdd27727741997181d832619b16',
            'fisico_financeiro.php' => 'df5d5e459206ddfaf55690ce6c12cf54314fd2003f9df4e93dcdc625875e4ead',
            '_rodape.php' => 'f3e1aae26ef526952e7367a81a2bbc4c2bd851d776d0513219cb40298523b9ea',
            // Etapa 9 (specs/relacao-de-bens.md): PDF novo da relação de bens
            'relacao_bens.php' => 'c56a6e28f332fe4c18f5d37e234402b4f0d74e0a6eb7648ed69606a589165254',
        ];
        $atuais = [];
        foreach (glob($this->raiz . '/templates/pdf/*.php') ?: [] as $f) {
            // DT-1: hash do conteúdo com quebras de linha normalizadas (o git no Windows converte LF em CRLF)
            $atuais[basename($f)] = hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($f)));
        }
        ksort($atuais);
        ksort($esperado);
        $this->assertSame($esperado, $atuais);
    }

    public function testOriginalCssBackupExists(): void
    {
        $this->assertFileExists($this->raiz . '/plans/anexos/app-css-original.css');
    }
}
