<?php

declare(strict_types=1);

namespace MAAF\Core\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

/**
 * Router
 * 
 * Stabil routing rendszer FastRoute alapján.
 * 
 * @version 1.0.0
 */
final class Router
{
    /**
     * @var array<int, array{method: string, route: string, handler: callable|array}>
     */
    private array $routes = [];

    private ?Dispatcher $dispatcher = null;

    /**
     * Add a route
     * 
     * @param string|array<string> $method HTTP method(s)
     * @param string $route Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function addRoute(string|array $method, string $route, callable|array $handler): void
    {
        $methods = is_array($method) ? $method : [$method];
        
        foreach ($methods as $m) {
            $this->routes[] = [
                'method' => strtoupper($m),
                'route' => $route,
                'handler' => $handler,
            ];
        }

        // Reset dispatcher to rebuild on next dispatch
        $this->dispatcher = null;
    }

    /**
     * Add GET route
     * 
     * @param string $route Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function get(string $route, callable|array $handler): void
    {
        $this->addRoute('GET', $route, $handler);
    }

    /**
     * Add POST route
     * 
     * @param string $route Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function post(string $route, callable|array $handler): void
    {
        $this->addRoute('POST', $route, $handler);
    }

    /**
     * Add PUT route
     * 
     * @param string $route Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function put(string $route, callable|array $handler): void
    {
        $this->addRoute('PUT', $route, $handler);
    }

    /**
     * Add PATCH route
     * 
     * @param string $route Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function patch(string $route, callable|array $handler): void
    {
        $this->addRoute('PATCH', $route, $handler);
    }

    /**
     * Add DELETE route
     * 
     * @param string $route Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function delete(string $route, callable|array $handler): void
    {
        $this->addRoute('DELETE', $route, $handler);
    }

    /**
     * Dispatch a request
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array{status: int, handler?: callable|array, params?: array<string, string>}
     */
    public function dispatch(string $method, string $uri): array
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = simpleDispatcher(function (RouteCollector $r) {
                foreach ($this->routes as $route) {
                    $r->addRoute($route['method'], $route['route'], $route['handler']);
                }
            });
        }

        return $this->dispatcher->dispatch($method, $uri);
    }

    /**
     * Get all routes
     * 
     * @return array<int, array{method: string, route: string, handler: callable|array}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
