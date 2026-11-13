<?php
declare(strict_types=1);

namespace Tests;

use App\Catalog\DocumentCatalog;
use App\Presenters\DocumentoLinhaPresenter as P;
use PHPUnit\Framework\TestCase;

final class DocumentoLinhaPresenterTest extends TestCase
{
    private const REL = [
        '01' => ['url' => '/projetos/7/balancete', 'rotulo' => 'Abrir balancete', 'n' => 0, 'unidade' => 'lançamento(s)'],
        '22' => ['url' => '/projetos/7/fisico-financeiro', 'rotulo' => 'Abrir físico-financeiro', 'n' => 0, 'unidade' => 'linha(s)'],
        '15' => ['url' => '/projetos/7/conciliacao', 'rotulo' => 'Abrir conciliação', 'n' => 0, 'unidade' => 'nota(s) fiscal(is)'],
    ];

    /** @return array<string,array> linhas indexadas por código */
    private function linhas(array $enviados = [], array $contagens = [], array $rel = self::REL): array
    {
        $out = [];
        foreach (P::linhas(['id' => 7], $enviados, $contagens, $rel) as $l) {
            $out[$l['codigo']] = $l;
        }
        return $out;
    }

    private function env(int $desatualizado = 0, string $nome = 'a.pdf', int $tamanho = 2048): array
    {
        return ['nome_original' => $nome, 'tamanho' => $tamanho, 'desatualizado' => $desatualizado];
    }

    private function sit(array $linha): string
    {
        return $linha['situacao']['texto'] . '|' . $linha['situacao']['classe'];
    }

    public function testReturnsTheTwentyFiveDocumentsInCatalogOrder(): void
    {
        $codigos = array_column(P::linhas(['id' => 7], [], [], self::REL), 'codigo');
        $this->assertSame(array_column(DocumentCatalog::all(), 'codigo'), $codigos);
        $this->assertCount(25, $codigos);
    }

    public function testEmptyProjectIsAllVazio(): void
    {
        foreach ($this->linhas() as $l) {
            $this->assertSame('Vazio|vazio', $this->sit($l), $l['codigo']);
            $this->assertNull($l['detalhe']);
            $this->assertFalse($l['desatualizado']);
        }
    }

    public function testUploadEmptyAndSent(): void
    {
        $vazio = $this->linhas()['18'];
        $this->assertSame(P::ACAO_UPLOAD, $vazio['acao']);
        $this->assertFalse($vazio['temArquivo']);
        $this->assertSame('application/pdf,.pdf', $vazio['envio']['accept']);
        $this->assertSame('Arquivo PDF do documento 18', $vazio['envio']['rotuloCampo']);
        $this->assertNull($vazio['envio']['dicaConversao']);
        $this->assertSame('Upload de arquivo', $vazio['tipo']);

        $enviado = $this->linhas(['18' => $this->env(0, 'parecer.pdf', 2048)])['18'];
        $this->assertSame('Enviado|ok', $this->sit($enviado));
        $this->assertSame('parecer.pdf · 2 KB', $enviado['detalhe']);
        $this->assertTrue($enviado['temArquivo']);
    }

    public function testConversionDocuments(): void
    {
        $l = $this->linhas(['08' => $this->env(0, 'fotos.pptx')]);
        $this->assertSame('Convertido|ok', $this->sit($l['08']));
        $this->assertSame(P::ACAO_UPLOAD, $l['08']['acao']);
        $this->assertStringContainsString('.pptx', $l['08']['envio']['accept']);
        $this->assertSame('Envie PPTX; o sistema converte para PDF.', $l['08']['envio']['dicaConversao']);
        $this->assertSame('image/png,.png', $l['16']['envio']['accept']);
        $this->assertSame('Envie PNG; o sistema converte para PDF.', $l['16']['envio']['dicaConversao']);
    }

    public function testMergeStates(): void
    {
        $l = $this->linhas(['02' => $this->env(1), '03' => $this->env()], ['02' => 3, '03' => 1, '04' => 2]);
        $this->assertSame('Desatualizado|aviso-tag', $this->sit($l['02']));
        $this->assertTrue($l['02']['desatualizado']);
        $this->assertSame('3 arquivo(s)', $l['02']['detalhe']);
        $this->assertSame('Gerado|ok', $this->sit($l['03']));
        $this->assertFalse($l['03']['desatualizado']);
        $this->assertSame('Não gerado|aviso-tag', $this->sit($l['04']));
        $this->assertSame('Vazio|vazio', $this->sit($l['05']));
        $this->assertSame(P::ACAO_JUNCAO, $l['05']['acao']);
        $this->assertSame('/projetos/7/documentos/05', $l['05']['urls']['base']);
    }

    public function testReportStates(): void
    {
        $rel = self::REL;
        $rel['01']['n'] = 3;
        $rel['15']['n'] = 1;
        $l = $this->linhas(['01' => $this->env(1), '22' => $this->env()], [], $rel);
        $this->assertSame('Desatualizado|aviso-tag', $this->sit($l['01']));
        $this->assertTrue($l['01']['desatualizado']);
        $this->assertSame('3 lançamento(s)', $l['01']['detalhe']);
        $this->assertSame(['url' => '/projetos/7/balancete', 'rotulo' => 'Abrir balancete'], $l['01']['formulario']);
        $this->assertSame('Gerado|ok', $this->sit($l['22']));
        $this->assertNull($l['22']['detalhe']);
        $this->assertSame('Não gerado|aviso-tag', $this->sit($l['15']));
        $this->assertSame('1 nota(s) fiscal(is)', $l['15']['detalhe']);
        $this->assertSame(P::ACAO_FORMULARIO, $l['15']['acao']);
    }

    public function testRelacaoDeBensIsNotAvailableYet(): void
    {
        $l = $this->linhas()['09'];
        $this->assertSame(P::ACAO_INDISPONIVEL, $l['acao']);
        $this->assertSame('Vazio|vazio', $this->sit($l));
        $this->assertNull($l['envio']);
    }

    public function testResumoZip(): void
    {
        $rel = self::REL;
        $rel['15']['n'] = 2;   // conciliação com notas, sem PDF: não gerada
        $linhas = P::linhas(['id' => 7], ['01' => $this->env(1), '18' => $this->env(), '02' => $this->env()], ['04' => 1], $rel);
        $this->assertSame(['comPdf' => 3, 'desatualizados' => ['01'], 'naoGerados' => ['04', '15']], P::resumoZip($linhas));
        $this->assertSame(['comPdf' => 0, 'desatualizados' => [], 'naoGerados' => []], P::resumoZip(P::linhas(['id' => 7], [], [], self::REL)));
    }

    public function testUrlsUseProjectIdAndCode(): void
    {
        $u = $this->linhas()['10a']['urls'];
        $this->assertSame('/projetos/7/documentos/10a/enviar', $u['enviar']);
        $this->assertSame('/projetos/7/documentos/10a/remover', $u['remover']);
        $this->assertSame('/projetos/7/documentos/10a/baixar', $u['baixar']);
        $this->assertSame('/projetos/7/documentos/10a/baixar?ver=1', $u['ver']);
    }
}
