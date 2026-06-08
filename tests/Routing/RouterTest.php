<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Http\Request;
use App\Http\Response;
use App\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        if (!defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost/apcu');
        }
    }

    public function testAddGetRoute(): void
    {
        $handler = function (Request $req): Response {
            return new Response('OK');
        };

        $this->router->get('/test', $handler);
        $routes = $this->router->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('GET', $routes[0]['method']);
        $this->assertSame('/test', $routes[0]['path']);
    }

    public function testAddPostRoute(): void
    {
        $handler = function (Request $req): Response {
            return new Response('Created', 201);
        };

        $this->router->post('/submit', $handler);
        $routes = $this->router->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('POST', $routes[0]['method']);
    }

    public function testMatchSimpleRoute(): void
    {
        $this->router->get('/hello', function (Request $req): Response {
            return new Response('Hello');
        });

        $request = $this->createRequest('GET', '/apcu/hello');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertIsCallable($result['handler']);
    }

    public function testMatchWithParameters(): void
    {
        $this->router->get('/user/{id}', function (Request $req, string $id): Response {
            return new Response("User: {$id}");
        });

        $request = $this->createRequest('GET', '/apcu/user/42');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('id', $result['params']);
        $this->assertSame('42', $result['params']['id']);
    }

    public function testMatchWithMultipleParameters(): void
    {
        $this->router->get('/post/{year}/{slug}', function (Request $req, string $year, string $slug): Response {
            return new Response("{$year}: {$slug}");
        });

        $request = $this->createRequest('GET', '/apcu/post/2024/hello-world');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame('2024', $result['params']['year']);
        $this->assertSame('hello-world', $result['params']['slug']);
    }

    public function testMethodMismatchReturnsNull(): void
    {
        $this->router->post('/data', function (Request $req): Response {
            return new Response('Created');
        });

        $request = $this->createRequest('GET', '/apcu/data');
        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    public function testUnknownRouteReturnsNull(): void
    {
        $this->router->get('/exists', function (Request $req): Response {
            return new Response('OK');
        });

        $request = $this->createRequest('GET', '/apcu/does-not-exist');
        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    public function testDispatchReturns404(): void
    {
        $request = $this->createRequest('GET', '/apcu/nonexistent');
        $response = $this->router->dispatch($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getBody());
    }

    public function testDispatchCallsHandler(): void
    {
        $this->router->get('/ping', function (Request $req): Response {
            return new Response('pong', 200, ['X-Custom' => 'value']);
        });

        $request = $this->createRequest('GET', '/apcu/ping');
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('pong', $response->getBody());
    }

    public function testRootRouteMatches(): void
    {
        $this->router->get('/', function (Request $req): Response {
            return new Response('Home');
        });

        $request = $this->createRequest('GET', '/apcu/');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
    }

    public function testMiddlewareIsStored(): void
    {
        $middleware = ['App\Http\Middleware\DummyMiddleware'];
        $this->router->get('/admin', function (Request $req): Response {
            return new Response('Admin');
        }, $middleware);

        $routes = $this->router->getRoutes();
        $this->assertSame($middleware, $routes[0]['middleware']);
    }

    private function createRequest(string $method, string $uri): Request
    {
        return new Request([], [], [], [], ['REQUEST_URI' => $uri], $uri, $method);
    }
}
