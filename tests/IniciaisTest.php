<?php
declare(strict_types=1);

namespace Tests;

use App\Presenters\Iniciais;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IniciaisTest extends TestCase
{
    public static function nomes(): array
    {
        return [
            'duas palavras com "do"' => ['Caxambu do Sul', 'CS'],
            'uma palavra' => ['Chapecó', 'CH'],
            'primeira e última' => ['São José dos Cedros', 'SC'],
            'conectivo no meio' => ['Rio do Sul', 'RS'],
            'espaços extras' => ['  Rio   do   Sul  ', 'RS'],
            'acentuada' => ['Érico Veríssimo', 'ÉV'],
            'minúsculas' => ['chapecó', 'CH'],
            'vazio' => ['', '?'],
        ];
    }

    #[DataProvider('nomes')]
    public function testIniciais(string $nome, string $esperado): void
    {
        $this->assertSame($esperado, Iniciais::de($nome));
    }
}
