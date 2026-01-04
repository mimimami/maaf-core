<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * Middleware Pipeline
 * 
 * Manages and executes a chain of middleware.
 */
final class MiddlewarePipeline
{
    /**
     * @var array<int, MiddlewareInterface|callable>
     */
    private array $middlewares = [];

    /**
     * Add a middleware to the pipeline.
     * 
     * @param MiddlewareInterface|callable(Request, callable): Response $middleware
     */
    public function add(MiddlewareInterface|callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Add multiple middlewares to the pipeline.
     * 
     * @param array<int, MiddlewareInterface|callable> $middlewares
     */
    public function addMany(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }

    /**
     * Execute the middleware pipeline.
     * 
     * @param Request $request The HTTP request
     * @param callable(Request): Response $finalHandler The final handler (controller)
     * @return Response The HTTP response
     */
    public function execute(Request $request, callable $finalHandler): Response
    {
        if (empty($this->middlewares)) {
            return $finalHandler($request);
        }

        // Build the pipeline by wrapping each middleware
        $pipeline = $finalHandler;

        // Reverse iterate to build the chain correctly
        for ($i = count($this->middlewares) - 1; $i >= 0; $i--) {
            $middleware = $this->middlewares[$i];
            
            $pipeline = function (Request $req) use ($middleware, $pipeline): Response {
                if ($middleware instanceof MiddlewareInterface) {
                    return $middleware->handle($req, $pipeline);
                }
                
                // Callable middleware
                return $middleware($req, $pipeline);
            };
        }

        return $pipeline($request);
    }

    /**
     * Get all registered middlewares.
     * 
     * @return array<int, MiddlewareInterface|callable>
     */
    public function all(): array
    {
        return $this->middlewares;
    }

    /**
     * Clear all middlewares.
     */
    public function clear(): void
    {
        $this->middlewares = [];
    }

    /**
     * Get the number of registered middlewares.
     */
    public function count(): int
    {
        return count($this->middlewares);
    }
}

