<?php
declare(strict_types=1);

namespace Tests;

use App\Domain\BalanceteCalculator;
use App\Domain\Dates;
use App\Domain\Money;
use App\Domain\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public static function validos(): array
    {
        return [
            'padrão BR' => ['497.152,49', 49715249],
            'sem milhar' => ['1234,5', 123450],
            'milhões' => ['1.234.567,89', 123456789],
            'ponto decimal' => ['1234.56', 123456],
            'inteiro' => ['500', 50000],
            'milhar sem decimal' => ['1.234', 123400],
            'com R$' => ['R$ 5.965,83', 596583],
            'centavos' => ['0,01', 1],
            'espaços' => ['  10,00 ', 1000],
            'zero' => ['0', 0],
        ];
    }

    #[DataProvider('validos')]
    public function testParseValid(string $in, int $esperado): void
    {
        $this->assertSame($esperado, Money::parse($in));
    }

    public static function invalidos(): array
    {
        return [
            'vazio' => [''], 'letras' => ['abc'], 'negativo' => ['-10,00'], 'duas vírgulas' => ['1,2,3'],
            'milhar errado' => ['12.34,56'], 'três decimais' => ['10,123'], 'só vírgula' => [','],
            'científico' => ['1e5'], 'enorme' => ['99999999999999,00'], 'acima do limite' => ['10.000.000.000,00'],
            'injeção' => ['10;DROP'],
        ];
    }

    #[DataProvider('invalidos')]
    public function testParseInvalid(string $in): void
    {
        $this->assertNull(Money::parse($in));
    }

    public function testFormat(): void
    {
        $this->assertSame('497.152,49', Money::format(49715249));
        $this->assertSame('0,05', Money::format(5));
        $this->assertSame('1,00', Money::format(100));
        $this->assertSame('-', Money::formatOrDash(0));
        $this->assertSame('1234,50', Money::input(123450));
    }

    public function testRoundTripIsExact(): void
    {
        foreach ([1, 99, 100, 123456789, 49715249, 599999999999] as $c) {
            $this->assertSame($c, Money::parse(Money::format($c)));
        }
    }

    public function testDates(): void
    {
        $this->assertSame('05/03/2026', Dates::br('2026-03-05'));
        $this->assertSame('05 de março de 2026', Dates::extenso('2026-03-05'));
        $this->assertSame('29 de fevereiro de 2028', Dates::extenso('2028-02-29'));
        $this->assertSame('', Dates::extenso('2026-02-30'));
        $this->assertSame('', Dates::br(''));
    }

    public function testTotaisFollowSpreadsheetLogic(): void
    {
        // Exemplo da planilha: transferência recebida e pagamento + retenção IRRF
        $l = [
            ['tipo' => 'receita', 'origem' => 'convenio', 'valor_centavos' => 49715249],
            ['tipo' => 'despesa', 'origem' => 'convenio', 'valor_centavos' => 49118666],
            ['tipo' => 'despesa', 'origem' => 'convenio', 'valor_centavos' => 596583],
            ['tipo' => 'receita', 'origem' => 'rendimentos', 'valor_centavos' => 1000],
            ['tipo' => 'despesa', 'origem' => 'contrapartida', 'valor_centavos' => 500],
        ];
        $t = BalanceteCalculator::totais($l);
        $this->assertSame(49716249, $t['receita']);
        $this->assertSame(49715749, $t['despesa']);
        $this->assertSame(500, $t['saldo']);
        $this->assertSame(49715249, $t['origens']['convenio']['receita']);
        $this->assertSame(49715249, $t['origens']['convenio']['despesa']);
        $this->assertSame(1000, $t['origens']['rendimentos']['receita']);
        $this->assertSame(500, $t['origens']['contrapartida']['despesa']);
    }

    public function testTotaisEmpty(): void
    {
        $t = BalanceteCalculator::totais([]);
        $this->assertSame(0, $t['receita'] + $t['despesa'] + $t['saldo']);
    }

    public function testFontePadrao(): void
    {
        $this->assertSame(1, BalanceteCalculator::fontePadrao('convenio'));
        $this->assertSame(1, BalanceteCalculator::fontePadrao('rendimentos'));
        $this->assertSame(2, BalanceteCalculator::fontePadrao('contrapartida'));
    }

    public function testValidatorLancamento(): void
    {
        $v = Validator::lancamento(['tipo' => 'despesa', 'origem' => 'contrapartida', 'descricao' => ' Pagamento  X ', 'valor' => '1.000,50', 'cnpj' => '36519422000620', 'data' => '2026-03-05']);
        $this->assertSame([], $v['erros']);
        $this->assertSame(2, $v['dados']['fonte']);
        $this->assertSame(100050, $v['dados']['valor_centavos']);
        $this->assertSame('36.519.422/0006-20', $v['dados']['cnpj']);
        $this->assertSame('Pagamento X', $v['dados']['descricao']);
    }

    public function testValidatorLancamentoRejectsBadInput(): void
    {
        $v = Validator::lancamento(['tipo' => 'x', 'origem' => 'y', 'descricao' => '', 'valor' => '0', 'cnpj' => '123', 'data' => '2026-13-01']);
        $this->assertCount(6, $v['erros']);
    }

    public function testExplicitFonteOverridesDefault(): void
    {
        $v = Validator::lancamento(['tipo' => 'receita', 'origem' => 'convenio', 'descricao' => 'x', 'valor' => '1', 'fonte' => '2']);
        $this->assertSame(2, $v['dados']['fonte']);
    }
}
