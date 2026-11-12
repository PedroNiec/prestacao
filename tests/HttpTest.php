<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class HttpTest extends TestCase
{
    public function testHomeRedirectsToProjetos(): void
    {
        $res = handle(new Request('GET', '/'), 'localhost:8000');
        $this->assertSame(303, $res->status);
        $this->assertSame('/projetos', $res->headers['Location']);
    }

    public function testProjetosPageLoadsOnLocalhost(): void
    {
        $res = handle(new Request('GET', '/projetos'), 'localhost:8000');
        $this->assertSame(200, $res->status);
        $this->assertStringContainsString('Projetos', $res->body);
    }

    public function testRejectsForeignHost(): void
    {
        $this->assertSame(403, handle(new Request('GET', '/'), 'evil.example.com')->status);
    }

    public function testPostWithoutCsrfIsRejected(): void
    {
        $this->assertSame(419, handle(new Request('POST', '/municipios', [], []), '127.0.0.1:8000')->status);
    }

    public function testCsrfTokenValidation(): void
    {
        $token = Csrf::token();
        $this->assertTrue(Csrf::valid($token));
        $this->assertFalse(Csrf::valid('errado'));
        $this->assertFalse(Csrf::valid(null));
    }

    public function testEscapesOutput(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
    }
}
