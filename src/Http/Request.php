<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    private array $get = [];
    private array $post = [];
    private array $cookies = [];
    private array $files = [];
    private array $server = [];
    private string $requestUri = '/';
    private string $method = 'GET';
    private array $jsonInput = [];

    public function __construct(
        array $get,
        array $post,
        array $cookies,
        array $files,
        array $server,
        string $requestUri,
        string $method
    ) {
        $this->get = $get;
        $this->post = $post;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->requestUri = $requestUri;
        $this->method = $method;

        // Parse JSON input for POST requests
        if ($this->method === 'POST' && 
            (isset($this->server['CONTENT_TYPE']) && str_contains($this->server['CONTENT_TYPE'], 'application/json'))) {
            $input = file_get_contents('php://input');
            $this->jsonInput = json_decode($input, true) ?? [];
        }
    }

    public static function createFromGlobals(): self
    {
        return new self(
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES,
            $_SERVER,
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
    }

    public function get(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        // Prioritize JSON input for POST requests
        if (!empty($this->jsonInput)) {
            return $this->jsonInput[$key] ?? $default;
        }
        return $this->post[$key] ?? $default;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function isAjax(): bool
    {
        return !empty($this->server['HTTP_X_REQUESTED_WITH']) && 
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function getQueryParams(): array
    {
        return $this->get;
    }

    public function getPostData(): array
    {
        return $this->post;
    }

    public function server(string $key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    public function getHeader(string $name, $default = null): ?string
    {
        $name = 'HTTP_' . strtoupper(str_replace(['-', '.'], '_', $name));
        return $this->server[$name] ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) ($this->get[$key] ?? $default);
    }

    public function postInt(string $key, int $default = 0): int
    {
        $value = !empty($this->jsonInput) 
            ? ($this->jsonInput[$key] ?? $default)
            : ($this->post[$key] ?? $default);
        return (int) $value;
    }

    public function getAlpha(string $key, string $default = ''): string
    {
        $value = $this->get[$key] ?? $default;
        return preg_replace('/[^a-zA-Z]/', '', $value);
    }

    public function getAlnum(string $key, string $default = ''): string
    {
        $value = $this->get[$key] ?? $default;
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }
}
