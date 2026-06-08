<?php

declare(strict_types=1);

namespace App\Routing;

use App\Http\Request;
use App\Http\Response;

class Router
{
    private array $routes = [];
    private array $routeParams = [];

    public function add(string $method, string $path, $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function match(Request $request): ?array
    {
        $requestMethod = $request->getMethod();
        $requestPath = $request->server('REQUEST_URI');

        // Parse the URL to get the path
        $parsedUrl = parse_url($requestPath);
        $requestPath = $parsedUrl['path'] ?? '/';

        // URL-decode the path (REQUEST_URI from Apache/PHP is URL-encoded)
        $requestPath = rawurldecode($requestPath);

        // Get the base path from the BASE_URL constant
        $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '';

        // Remove the base path from the request path
        if ($basePath && strpos($requestPath, $basePath) === 0) {
            $requestPath = substr($requestPath, strlen($basePath));
        }

        // Normalize the path
        $requestPath = '/' . ltrim($requestPath, '/');
        $requestPath = rtrim($requestPath, '/') ?: '/';

        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);

            if ($route['method'] === $requestMethod && preg_match($pattern, $requestPath, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middleware' => $route['middleware']
                ];
            }
        }

        return null;
    }


    private function convertToRegex(string $path): string
    {
        // Remove leading/trailing slashes
        $path = trim($path, '/');

        // Convert route parameters to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);

        // Add start/end anchors and make case-insensitive
        return '#^/' . $pattern . '$#i';
    }


    public function dispatch(Request $request): Response
    {
        $route = $this->match($request);

        if (!$route) {
            return new Response('Not Found', 404);
        }

        // Apply middleware
        foreach ($route['middleware'] as $middleware) {
            $middlewareInstance = new $middleware();
            $response = $middlewareInstance->handle($request, function ($request) use ($route) {
                return $this->callHandler($route['handler'], $request, $route['params']);
            });

            if ($response instanceof Response) {
                return $response;
            }
        }

        // If no middleware or middleware passes, call the handler
        return $this->callHandler($route['handler'], $request, $route['params']);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function callHandler($handler, Request $request, array $params)
    {
        if (is_callable($handler)) {
            return $handler($request, ...array_values($params));
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            $controller = 'App\\Controller\\' . $controller;

            if (class_exists($controller)) {
                $controllerInstance = new $controller();

                if (method_exists($controllerInstance, $method)) {
                    return $controllerInstance->$method($request, ...array_values($params));
                }
            }
        }


        return new Response('Not Found', 404);
    }
}
