<?php
declare(strict_types=1);

namespace App\Domain;

/** Normalização e validação dos formulários de município e projeto. */
final class Validator
{
    private const UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

    private static function txt(array $in, string $k, int $max = 500): string
    {
        $v = $in[$k] ?? '';
        $v = is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v) ?? '') : '';
        return mb_substr($v, 0, $max);
    }

    /** @return array{dados:array<string,string>,erros:string[]} */
    public static function municipio(array $in): array
    {
        $d = [
            'nome' => self::txt($in, 'nome', 120),
            'uf' => strtoupper(self::txt($in, 'uf', 2)),
            'prefeito_nome' => self::txt($in, 'prefeito_nome', 120),
            'contador_nome' => self::txt($in, 'contador_nome', 120),
            'contador_crc' => self::txt($in, 'contador_crc', 40),
        ];
        $erros = [];
        if ($d['nome'] === '') {
            $erros[] = 'Informe o nome do município.';
        }
        if (!in_array($d['uf'], self::UFS, true)) {
            $erros[] = 'Informe uma UF válida.';
        }
        return ['dados' => $d, 'erros' => $erros];
    }

    /**
     * Assinaturas em branco herdam do município (RF-02), mas continuam editáveis.
     * @return array{dados:array<string,mixed>,erros:string[]}
     */
    public static function projeto(array $in, ?array $municipio): array
    {
        $d = [
            'municipio_id' => (int) ($in['municipio_id'] ?? 0),
            'concedente' => self::txt($in, 'concedente'),
            'processo' => self::txt($in, 'processo'),
            'nr_pp' => self::txt($in, 'nr_pp', 60),
            'nr_ne' => self::txt($in, 'nr_ne', 60),
            'nr_nl' => self::txt($in, 'nr_nl', 60),
            'objeto' => self::txt($in, 'objeto', 1000),
            'local' => self::txt($in, 'local', 120),
            'data_assinatura' => self::txt($in, 'data_assinatura', 10),
            'prefeito_nome' => self::txt($in, 'prefeito_nome', 120),
            'contador_nome' => self::txt($in, 'contador_nome', 120),
            'contador_crc' => self::txt($in, 'contador_crc', 40),
        ];
        $erros = [];
        if ($municipio === null) {
            $erros[] = 'Selecione um município.';
        } else {
            foreach (['prefeito_nome', 'contador_nome', 'contador_crc'] as $c) {
                if ($d[$c] === '') {
                    $d[$c] = (string) $municipio[$c];
                }
            }
            if ($d['local'] === '') {
                $d['local'] = $municipio['nome'] . ' - ' . $municipio['uf'];
            }
        }
        if ($d['objeto'] === '') {
            $erros[] = 'Informe o objeto do projeto.';
        }
        if ($d['data_assinatura'] !== '') {
            $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $d['data_assinatura']);
            if ($dt === false || $dt->format('Y-m-d') !== $d['data_assinatura']) {
                $erros[] = 'Data de assinatura inválida.';
            }
        }
        return ['dados' => $d, 'erros' => $erros];
    }

    /**
     * Lançamento do balancete (RF-13).
     * @return array{dados:array<string,mixed>,erros:string[]}
     */
    public static function lancamento(array $in): array
    {
        $tipo = ($in['tipo'] ?? '') === 'receita' ? 'receita' : (($in['tipo'] ?? '') === 'despesa' ? 'despesa' : '');
        $origem = (string) ($in['origem'] ?? '');
        $fonteIn = (string) ($in['fonte'] ?? '');
        $d = [
            'boletim_parcela' => self::txt($in, 'boletim_parcela', 60),
            'descricao' => self::txt($in, 'descricao', 300),
            'cnpj' => self::txt($in, 'cnpj', 30),
            'nota_fiscal' => self::txt($in, 'nota_fiscal', 40),
            'nota_empenho' => self::txt($in, 'nota_empenho', 40),
            'doc' => self::txt($in, 'doc', 60),
            'data' => self::txt($in, 'data', 10),
            'origem' => $origem,
            'tipo' => $tipo,
            'fonte' => in_array($fonteIn, ['1', '2'], true) ? (int) $fonteIn : BalanceteCalculator::fontePadrao($origem),
            'valor_centavos' => 0,
        ];
        $erros = [];
        if ($tipo === '') {
            $erros[] = 'Informe se o lançamento é Receita ou Despesa.';
        }
        if (!array_key_exists($origem, BalanceteCalculator::ORIGENS)) {
            $erros[] = 'Selecione a origem do recurso.';
        }
        if ($d['descricao'] === '') {
            $erros[] = 'Informe a descrição.';
        }
        $valor = Money::parse((string) ($in['valor'] ?? ''));
        if ($valor === null || $valor <= 0) {
            $erros[] = 'Informe um valor maior que zero (ex.: 1.234,56).';
        } else {
            $d['valor_centavos'] = $valor;
        }
        if ($d['data'] !== '' && Dates::parse($d['data']) === null) {
            $erros[] = 'Data inválida.';
        }
        if ($d['cnpj'] !== '') {
            $digitos = preg_replace('/\D/', '', $d['cnpj']) ?? '';
            if (strlen($digitos) !== 14) {
                $erros[] = 'CNPJ deve ter 14 dígitos.';
            } else {
                $d['cnpj'] = sprintf('%s.%s.%s/%s-%s', substr($digitos, 0, 2), substr($digitos, 2, 3), substr($digitos, 5, 3), substr($digitos, 8, 4), substr($digitos, 12, 2));
            }
        }
        return ['dados' => $d, 'erros' => $erros];
    }

    /** Campos numéricos do físico-financeiro: rótulo => nome do campo. */
    public const FISICO_QUANTIDADES = [
        'fis_prog_periodo' => 'Físico: programado no período', 'fis_exec_periodo' => 'Físico: executado no período',
        'fis_prog_ate' => 'Físico: programado até o período', 'fis_exec_ate' => 'Físico: executado até o período',
    ];
    public const FISICO_VALORES = [
        'fin_conc_periodo' => 'Financeiro: concedente no período', 'fin_exec_periodo' => 'Financeiro: executor no período',
        'fin_rend_periodo' => 'Financeiro: outros rendimentos no período',
        'fin_conc_ate' => 'Financeiro: concedente até o período', 'fin_exec_ate' => 'Financeiro: executor até o período',
    ];

    /**
     * Linha do relatório físico-financeiro (RF-14). Campos numéricos em branco valem zero.
     * @return array{dados:array<string,mixed>,erros:string[]}
     */
    public static function fisicoFinanceiro(array $in): array
    {
        $d = [
            'meta' => self::txt($in, 'meta', 20),
            'etapa' => self::txt($in, 'etapa', 20),
            'descricao' => self::txt($in, 'descricao', 500),
            'unidade' => self::txt($in, 'unidade', 20),
        ];
        $erros = [];
        if ($d['descricao'] === '') {
            $erros[] = 'Informe a descrição.';
        }
        foreach (self::FISICO_QUANTIDADES + self::FISICO_VALORES as $campo => $rotulo) {
            $raw = trim((string) ($in[$campo] ?? ''));
            if ($raw === '') {
                $d[$campo] = 0;
                continue;
            }
            $v = Money::parse($raw);
            if ($v === null) {
                $erros[] = "Valor inválido em «{$rotulo}» (use números positivos, ex.: 1.234,56).";
                $d[$campo] = 0;
            } else {
                $d[$campo] = $v;
            }
        }
        return ['dados' => $d, 'erros' => $erros];
    }

    /** @return array{dados:array<string,mixed>,erros:string[]} Nota fiscal da conciliação (RF-15). */
    public static function conciliacaoNota(array $in): array
    {
        $d = ['nota_fiscal' => self::txt($in, 'nota_fiscal', 40)];
        $erros = [];
        if ($d['nota_fiscal'] === '') {
            $erros[] = 'Informe o número da nota fiscal.';
        }
        $campos = ['bruto_centavos' => ['bruto', 'valor bruto'], 'ir_centavos' => ['ir', 'IR'], 'iss_centavos' => ['iss', 'ISS'],
                   'inss_centavos' => ['inss', 'INSS'], 'outros_centavos' => ['outros', 'outros descontos']];
        foreach ($campos as $col => [$campo, $rotulo]) {
            $raw = trim((string) ($in[$campo] ?? ''));
            $v = $raw === '' ? 0 : Money::parse($raw);
            if ($v === null) {
                $erros[] = "Valor inválido em «{$rotulo}» (use números positivos, ex.: 1.234,56).";
                $v = 0;
            }
            $d[$col] = $v;
        }
        if ($d['bruto_centavos'] <= 0) {
            $erros[] = 'Informe o valor bruto da nota (maior que zero).';
        }
        $ret = $d['ir_centavos'] + $d['iss_centavos'] + $d['inss_centavos'] + $d['outros_centavos'];
        if ($d['bruto_centavos'] > 0 && $ret > $d['bruto_centavos']) {
            $erros[] = 'As retenções e descontos somam mais que o valor bruto da nota.';
        }
        return ['dados' => $d, 'erros' => $erros];
    }

    /** @return array{dados:array<string,mixed>,erros:string[]} Dados avulsos da conciliação (textos, devoluções, extrato). */
    public static function conciliacaoConfig(array $in): array
    {
        $d = [
            'titulo' => self::txt($in, 'titulo', 200),
            'rotulo_extrato' => self::txt($in, 'rotulo_extrato', 60),
            'extrato_descricao' => self::txt($in, 'extrato_descricao', 200),
            'data_saldo' => self::txt($in, 'data_saldo', 10),
        ];
        $erros = [];
        if ($d['data_saldo'] !== '' && Dates::parse($d['data_saldo']) === null) {
            $erros[] = 'Data do saldo inválida.';
        }
        $campos = ['saldo_cc' => 'Saldo da conta corrente', 'saldo_aplic' => 'Saldo da aplicação'] + ConciliacaoCalculator::DEVOLUCOES;
        foreach ($campos as $col => $rotulo) {
            $raw = trim((string) ($in[$col] ?? ''));
            $v = $raw === '' ? 0 : Money::parse($raw);
            if ($v === null) {
                $erros[] = "Valor inválido em «{$rotulo}» (use números positivos, ex.: 1.234,56).";
                $v = 0;
            }
            $d[$col] = $v;
        }
        return ['dados' => $d, 'erros' => $erros];
    }
}
