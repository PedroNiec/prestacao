<?php
declare(strict_types=1);

namespace App\Catalog;

/** Lista fixa dos 25 documentos da prestação de contas (spec, seção 6). */
final class DocumentCatalog
{
    public const MERGE = 'merge';
    public const UPLOAD = 'upload';
    public const CONVERT_PPTX = 'convert_pptx';
    public const CONVERT_PNG = 'convert_png';
    public const REPORT = 'report';

    private const LABELS = [
        self::MERGE => 'Junção de PDFs',
        self::UPLOAD => 'Upload de arquivo',
        self::CONVERT_PPTX => 'Converte PowerPoint em PDF',
        self::CONVERT_PNG => 'Converte imagem PNG em PDF',
        self::REPORT => 'Formulário no sistema',
    ];

    /** @return array<string,array{codigo:string,nome:string,comportamento:string,arquivo:string}> */
    public static function all(): array
    {
        static $docs = null;
        if ($docs !== null) {
            return $docs;
        }
        $lista = [
            ['01', 'Balancete', self::REPORT, 'balancete'],
            ['02', 'Notas de empenho', self::MERGE, 'notas-de-empenho'],
            ['03', 'Notas fiscais', self::MERGE, 'notas-fiscais'],
            ['04', 'Comprovantes de pagamentos', self::MERGE, 'comprovantes-de-pagamentos'],
            ['05', 'Ordens de serviços', self::MERGE, 'ordens-de-servicos'],
            ['06', 'Relatório de medições', self::MERGE, 'relatorio-de-medicoes'],
            ['07', 'Laudo técnico de engenharia', self::MERGE, 'laudo-tecnico-de-engenharia'],
            ['08', 'Acervo fotográfico', self::CONVERT_PPTX, 'acervo-fotografico'],
            ['09', 'Relação de bens adquiridos/produzidos/construídos', self::REPORT, 'relacao-de-bens'],
            ['10a', 'ART de execução', self::UPLOAD, 'art-de-execucao'],
            ['10b', 'ART de fiscalização', self::UPLOAD, 'art-de-fiscalizacao'],
            ['11', 'Aporte de contrapartida', self::UPLOAD, 'aporte-de-contrapartida'],
            ['12', 'Execução de contrapartida', self::UPLOAD, 'execucao-de-contrapartida'],
            ['13', 'Extratos da conta corrente', self::MERGE, 'extratos-conta-corrente'],
            ['14', 'Extratos da conta aplicação', self::MERGE, 'extratos-conta-aplicacao'],
            ['15', 'Conciliação bancária', self::REPORT, 'conciliacao-bancaria'],
            ['16', 'Identificação oficial (placa da obra/serviço/bens)', self::CONVERT_PNG, 'identificacao-oficial'],
            ['17', 'Cadastro Nacional de Obras (CNO)', self::UPLOAD, 'cno'],
            ['18', 'Parecer do Controle Interno', self::UPLOAD, 'parecer-do-controle-interno'],
            ['19', 'Certidão negativa de débitos (CND)', self::UPLOAD, 'cnd'],
            ['20', 'Comprovante de devolução de saldos', self::UPLOAD, 'devolucao-de-saldos'],
            ['21', 'Comprovante de recolhimento de rendimentos', self::UPLOAD, 'recolhimento-de-rendimentos'],
            ['22', 'Relatório de execução físico-financeiro', self::REPORT, 'fisico-financeiro'],
            ['23', 'Termo de aceitação definitiva do objeto', self::UPLOAD, 'termo-de-aceitacao-definitiva'],
            ['24', 'Termo de encerramento da execução do objeto', self::UPLOAD, 'termo-de-encerramento'],
        ];
        $docs = [];
        foreach ($lista as [$codigo, $nome, $comp, $slug]) {
            $docs[$codigo] = [
                'codigo' => $codigo,
                'nome' => $nome,
                'comportamento' => $comp,
                'arquivo' => $codigo . '-' . $slug . '.pdf',
            ];
        }
        return $docs;
    }

    public static function find(string $codigo): ?array
    {
        return self::all()[$codigo] ?? null;
    }

    /**
     * Extensões aceitas no envio direto de um documento de upload.
     * DEC-03 (spec): formatos do CNO (17) ainda pendentes; por ora, somente PDF.
     *
     * @return string[]
     */
    public static function extensoesUpload(string $codigo): array
    {
        return match (self::find($codigo)['comportamento'] ?? null) {
            self::CONVERT_PNG => ['png'],
            self::CONVERT_PPTX => ['pptx'],
            default => ['pdf'],
        };
    }

    /** Documentos cujo arquivo enviado é convertido em PDF pelo sistema. */
    public static function ehConversao(string $codigo): bool
    {
        return in_array(self::find($codigo)['comportamento'] ?? null, [self::CONVERT_PNG, self::CONVERT_PPTX], true);
    }

    public static function label(string $comportamento): string
    {
        return self::LABELS[$comportamento] ?? $comportamento;
    }
}
