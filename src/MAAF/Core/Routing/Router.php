<?php

declare(strict_types=1);

namespace MAAF\Core\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

/**
 * Router
 * 
 * MAAF Router for handling route registration and dispatching.
 */
final class Router
{
    private Dispatcher $dispatcher;

    /**
     * @var array<int, array{string, string, array{string, string}}>
     */
    private array $routes = [];

    public function __construct(?string $routesFile = null)
    {
        if ($routesFile !== null) {
            $this->loadRoutesFromFile($routesFile);
        }
    }

    /**
     * Load routes from a file.
     */
    public function loadRoutesFromFile(string $routesFile): void
    {
        $routes = require $routesFile;
        foreach ($routes as $route) {
            [$method, $path, $handler] = $route;
            $this->addRoute($method, $path, $handler);
        }
    }

    /**
     * Add a route.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path Route path
     * @param array{string, string} $handler [ControllerClass, method]
     */
    public function addRoute(string $method, string $path, array $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    /**
     * Build the FastRoute dispatcher.
     */
    public function buildDispatcher(): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $r): void {
            foreach ($this->routes as $route) {
                [$method, $path, $handler] = $route;
                $r->addRoute($method, $path, $handler);
            }
        });
    }

    /**
     * Dispatches the request and returns FastRoute result array.
     *
     * @return array<int, mixed>
     */
    public function dispatch(string $httpMethod, string $uri): array
    {
        if (!isset($this->dispatcher)) {
            $this->dispatcher = $this->buildDispatcher();
        }

        // ensure $uri is a string with no query
        $uri = (string) strtok($uri, '?');

        return $this->dispatcher->dispatch($httpMethod, $uri);
    }

    /**
     * Get all registered routes.
     *
     * @return array<int, array{string, string, array{string, string}}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}

