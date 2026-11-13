<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Catalog\DocumentCatalog;

/**
 * Prepara as 25 linhas de documentos da tela do Projeto: situação, detalhe, tipo e ação de cada uma.
 * Só apresentação: não consulta o banco nem altera dados (spec melhoria-visual-tela-projeto, RN-P02).
 */
final class DocumentoLinhaPresenter
{
    public const ACAO_UPLOAD = 'upload';            // upload direto ou conversão
    public const ACAO_JUNCAO = 'juncao';
    public const ACAO_FORMULARIO = 'formulario';    // formulário já implementado
    public const ACAO_INDISPONIVEL = 'indisponivel'; // formulário ainda não implementado

    private const ACCEPT = [
        'pdf' => 'application/pdf,.pdf',
        'png' => 'image/png,.png',
        'pptx' => '.pptx,application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    /**
     * @param array<string,mixed>  $projeto    precisa de 'id'
     * @param array<string,array>  $enviados   documento gerado/enviado, por código
     * @param array<string,int>    $contagens  arquivos de junção, por código
     * @param array<string,array>  $relatorios formulários prontos: url, rotulo, n, unidade
     * @return list<array<string,mixed>>
     */
    public static function linhas(array $projeto, array $enviados, array $contagens, array $relatorios): array
    {
        $linhas = [];
        foreach (DocumentCatalog::all() as $d) {
            $linhas[] = self::linha((int) $projeto['id'], $d, $enviados[$d['codigo']] ?? null,
                (int) ($contagens[$d['codigo']] ?? 0), $relatorios[$d['codigo']] ?? null);
        }
        return $linhas;
    }

    /**
     * Resumo para o botão do ZIP (specs/zip-prestacao.md, RF-Z04 e RF-Z05), a partir das linhas já calculadas.
     * @param list<array<string,mixed>> $linhas
     * @return array{comPdf:int,desatualizados:list<string>,naoGerados:list<string>}
     */
    public static function resumoZip(array $linhas): array
    {
        $r = ['comPdf' => 0, 'desatualizados' => [], 'naoGerados' => []];
        foreach ($linhas as $l) {
            if ($l['temArquivo']) {
                $r['comPdf']++;
            }
            if ($l['desatualizado']) {
                $r['desatualizados'][] = (string) $l['codigo'];
            } elseif ($l['situacao']['texto'] === 'Não gerado') {
                $r['naoGerados'][] = (string) $l['codigo'];
            }
        }
        return $r;
    }

    /** @return array<string,mixed> */
    private static function linha(int $projetoId, array $d, ?array $env, int $nArq, ?array $rel): array
    {
        $codigo = $d['codigo'];
        $base = '/projetos/' . $projetoId . '/documentos/' . $codigo;
        $comp = $d['comportamento'];
        $converte = DocumentCatalog::ehConversao($codigo);
        $desatualizado = $env !== null && (int) $env['desatualizado'] === 1;

        $linha = [
            'codigo' => $codigo,
            'nome' => $d['nome'],
            'tipo' => DocumentCatalog::label($comp),
            'situacao' => self::situacao('Vazio', 'vazio'),
            'detalhe' => null,
            'desatualizado' => false,
            'acao' => self::ACAO_INDISPONIVEL,
            'temArquivo' => $env !== null,
            'urls' => ['base' => $base, 'baixar' => $base . '/baixar', 'ver' => $base . '/baixar?ver=1',
                       'enviar' => $base . '/enviar', 'remover' => $base . '/remover'],
            'formulario' => null,
            'envio' => null,
        ];

        if ($comp === DocumentCatalog::MERGE) {
            $linha['acao'] = self::ACAO_JUNCAO;
            $linha['situacao'] = self::situacaoGerada($env, $nArq > 0);
            $linha['desatualizado'] = $desatualizado;
            $linha['detalhe'] = $nArq > 0 ? $nArq . ' arquivo(s)' : null;
        } elseif ($comp === DocumentCatalog::REPORT && $rel !== null) {
            $n = (int) ($rel['n'] ?? 0);
            $linha['acao'] = self::ACAO_FORMULARIO;
            $linha['situacao'] = self::situacaoGerada($env, $n > 0);
            $linha['desatualizado'] = $desatualizado;
            $linha['detalhe'] = $n > 0 ? $n . ' ' . ($rel['unidade'] ?? '') : null;
            $linha['formulario'] = ['url' => (string) $rel['url'], 'rotulo' => (string) $rel['rotulo']];
        } else {
            if ($env !== null) {
                $linha['situacao'] = self::situacao($converte ? 'Convertido' : 'Enviado', 'ok');
                $linha['detalhe'] = $env['nome_original'] . ' · ' . format_bytes((int) $env['tamanho']);
            }
            if ($comp === DocumentCatalog::UPLOAD || $converte) {
                $ext = DocumentCatalog::extensoesUpload($codigo)[0];
                $linha['acao'] = self::ACAO_UPLOAD;
                $linha['envio'] = [
                    'ext' => $ext,
                    'accept' => self::ACCEPT[$ext],
                    'rotuloCampo' => 'Arquivo ' . strtoupper($ext) . ' do documento ' . $codigo,
                    'dicaConversao' => $converte ? 'Envie ' . strtoupper($ext) . '; o sistema converte para PDF.' : null,
                ];
            }
        }
        return $linha;
    }

    /** Junção e formulário: Desatualizado → Gerado → Não gerado (há conteúdo) → Vazio. */
    private static function situacaoGerada(?array $env, bool $temConteudo): array
    {
        if ($env !== null && (int) $env['desatualizado'] === 1) {
            return self::situacao('Desatualizado', 'aviso-tag');
        }
        if ($env !== null) {
            return self::situacao('Gerado', 'ok');
        }
        return $temConteudo ? self::situacao('Não gerado', 'aviso-tag') : self::situacao('Vazio', 'vazio');
    }

    private static function situacao(string $texto, string $classe): array
    {
        return ['texto' => $texto, 'classe' => $classe];
    }
}
