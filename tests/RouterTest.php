<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesWithParameters(): void
    {
        $router = new Router();
        $router->get('/projetos/{id}', fn (Request $r, string $id) => Response::html("p$id"));

        $res = $router->dispatch(new Request('GET', '/projetos/42'));
        $this->assertSame(200, $res->status);
        $this->assertSame('p42', $res->body);
    }

    public function testUnknownPathReturns404(): void
    {
        $this->assertSame(404, (new Router())->dispatch(new Request('GET', '/nada'))->status);
    }

    public function testWrongMethodReturns405(): void
    {
        $router = new Router();
        $router->post('/salvar', fn () => Response::html('ok'));
        $this->assertSame(405, $router->dispatch(new Request('GET', '/salvar'))->status);
    }
}
