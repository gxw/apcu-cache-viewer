<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testGetReturnsQueryParam(): void
    {
        $request = new Request(['foo' => 'bar'], [], [], [], [], '/', 'GET');
        $this->assertSame('bar', $request->get('foo'));
    }

    public function testGetReturnsDefaultForMissing(): void
    {
        $request = new Request([], [], [], [], [], '/', 'GET');
        $this->assertSame('default', $request->get('missing', 'default'));
    }

    public function testPostReturnsPostData(): void
    {
        $request = new Request([], ['name' => 'test'], [], [], [], '/', 'POST');
        $this->assertSame('test', $request->post('name'));
    }

    public function testPostReturnsDefaultForMissing(): void
    {
        $request = new Request([], [], [], [], [], '/', 'POST');
        $this->assertNull($request->post('missing'));
    }

    public function testGetMethod(): void
    {
        $request = new Request([], [], [], [], [], '/', 'POST');
        $this->assertSame('POST', $request->getMethod());
    }

    public function testIsGet(): void
    {
        $request = new Request([], [], [], [], [], '/', 'GET');
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
    }

    public function testIsPost(): void
    {
        $request = new Request([], [], [], [], [], '/', 'POST');
        $this->assertTrue($request->isPost());
        $this->assertFalse($request->isGet());
    }

    public function testIsAjaxReturnsTrue(): void
    {
        $request = new Request([], [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], '/', 'GET');
        $this->assertTrue($request->isAjax());
    }

    public function testIsAjaxReturnsFalse(): void
    {
        $request = new Request([], [], [], [], [], '/', 'GET');
        $this->assertFalse($request->isAjax());
    }

    public function testGetQueryParams(): void
    {
        $query = ['page' => '1', 'sort' => 'key'];
        $request = new Request($query, [], [], [], [], '/', 'GET');
        $this->assertSame($query, $request->getQueryParams());
    }

    public function testGetPostData(): void
    {
        $post = ['key' => 'value'];
        $request = new Request([], $post, [], [], [], '/', 'POST');
        $this->assertSame($post, $request->getPostData());
    }

    public function testServer(): void
    {
        $request = new Request([], [], [], [], ['HTTP_HOST' => 'example.com'], '/', 'GET');
        $this->assertSame('example.com', $request->server('HTTP_HOST'));
    }

    public function testServerReturnsDefault(): void
    {
        $request = new Request([], [], [], [], [], '/', 'GET');
        $this->assertSame('default', $request->server('MISSING', 'default'));
    }

    public function testGetHeader(): void
    {
        $request = new Request([], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '/', 'GET');
        $this->assertSame('application/json', $request->getHeader('Content-Type'));
    }

    public function testGetHeaderReturnsDefault(): void
    {
        $request = new Request([], [], [], [], [], '/', 'GET');
        $this->assertNull($request->getHeader('X-Missing'));
    }

    /** @dataProvider sanitizationProvider */
    public function testGetInt(string $key, array $get, int $expected, int $default): void
    {
        $request = new Request($get, [], [], [], [], '/', 'GET');
        $this->assertSame($expected, $request->getInt($key, $default));
    }

    public static function sanitizationProvider(): array
    {
        return [
            'existing int'    => ['page', ['page' => '3'], 3, 1],
            'missing default' => ['count', [], 1, 1],
            'string value'    => ['num', ['num' => '42abc'], 42, 0],
        ];
    }

    public function testPostInt(): void
    {
        $request = new Request([], ['count' => '7'], [], [], [], '/', 'POST');
        $this->assertSame(7, $request->postInt('count'));
    }

    public function testGetAlpha(): void
    {
        $request = new Request(['name' => 'Hello123!'], [], [], [], [], '/', 'GET');
        $this->assertSame('Hello', $request->getAlpha('name'));
    }

    public function testGetAlnum(): void
    {
        $request = new Request(['code' => 'ABC-123_def'], [], [], [], [], '/', 'GET');
        $this->assertSame('ABC123def', $request->getAlnum('code'));
    }

    public function testCreateFromGlobalsReturnsInstance(): void
    {
        $request = Request::createFromGlobals();
        $this->assertInstanceOf(Request::class, $request);
    }
}
