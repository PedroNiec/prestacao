<?php
declare(strict_types=1);

namespace Tests;

use App\Domain\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private array $municipio = [
        'id' => 1, 'nome' => 'Caxambu do Sul', 'uf' => 'SC',
        'prefeito_nome' => 'Prefeito A', 'contador_nome' => 'Contador B', 'contador_crc' => 'CRC/SC 1',
    ];

    public function testMunicipioRequiresNomeAndValidUf(): void
    {
        $v = Validator::municipio(['nome' => '  ', 'uf' => 'XX']);
        $this->assertCount(2, $v['erros']);
    }

    public function testMunicipioNormalizesInput(): void
    {
        $v = Validator::municipio(['nome' => "  Caxambu   do Sul ", 'uf' => 'sc']);
        $this->assertSame([], $v['erros']);
        $this->assertSame('Caxambu do Sul', $v['dados']['nome']);
        $this->assertSame('SC', $v['dados']['uf']);
    }

    public function testProjetoInheritsSignatoriesWhenBlank(): void
    {
        $v = Validator::projeto(['municipio_id' => 1, 'objeto' => 'Ônibus'], $this->municipio);
        $this->assertSame([], $v['erros']);
        $this->assertSame('Prefeito A', $v['dados']['prefeito_nome']);
        $this->assertSame('Contador B', $v['dados']['contador_nome']);
        $this->assertSame('CRC/SC 1', $v['dados']['contador_crc']);
        $this->assertSame('Caxambu do Sul - SC', $v['dados']['local']);
    }

    public function testProjetoKeepsEditedSignatories(): void
    {
        $v = Validator::projeto(['municipio_id' => 1, 'objeto' => 'X', 'prefeito_nome' => 'Outro'], $this->municipio);
        $this->assertSame('Outro', $v['dados']['prefeito_nome']);
    }

    public function testProjetoRequiresMunicipioAndObjeto(): void
    {
        $v = Validator::projeto(['objeto' => ''], null);
        $this->assertCount(2, $v['erros']);
    }

    public function testProjetoRejectsInvalidDate(): void
    {
        $v = Validator::projeto(['municipio_id' => 1, 'objeto' => 'X', 'data_assinatura' => '2026-02-31'], $this->municipio);
        $this->assertContains('Data de assinatura inválida.', $v['erros']);
    }
}
