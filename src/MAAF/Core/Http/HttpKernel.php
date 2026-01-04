<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

use FastRoute\Dispatcher as FastRouteDispatcher;
use Psr\Container\ContainerInterface;
use MAAF\Core\Routing\Router;

/**
 * HTTP Kernel
 * 
 * MAAF HTTP Kernel for handling requests and dispatching to controllers.
 * 
 * Supports full middleware pipeline with multiple middlewares.
 */
final class HttpKernel
{
    private FastRouteDispatcher $dispatcher;
    private ?ContainerInterface $container = null;
    private MiddlewarePipeline $middlewarePipeline;

    /**
     * Create HttpKernel with Router (as per MAAF book).
     * 
     * @param Router|FastRouteDispatcher $routerOrDispatcher Router or Dispatcher instance
     * @param ControllerResolver $resolver Controller resolver
     * @param ContainerInterface|null $container Optional container for middleware support
     */
    public function __construct(
        Router|FastRouteDispatcher $routerOrDispatcher,
        private ControllerResolver $resolver,
        ?ContainerInterface $container = null
    ) {
        // Support both Router (as per book) and Dispatcher (for backward compatibility)
        if ($routerOrDispatcher instanceof Router) {
            $this->dispatcher = $routerOrDispatcher->buildDispatcher();
        } else {
            $this->dispatcher = $routerOrDispatcher;
        }
        
        // Store container if provided (for middleware support)
        if ($container !== null) {
            $this->container = $container;
        }

        // Initialize middleware pipeline
        $this->middlewarePipeline = new MiddlewarePipeline();
    }

    /**
     * Add a middleware to the pipeline.
     * 
     * @param MiddlewareInterface|callable(Request, callable): Response $middleware
     */
    public function addMiddleware(MiddlewareInterface|callable $middleware): void
    {
        $this->middlewarePipeline->add($middleware);
    }

    /**
     * Add multiple middlewares to the pipeline.
     * 
     * @param array<int, MiddlewareInterface|callable> $middlewares
     */
    public function addMiddlewares(array $middlewares): void
    {
        $this->middlewarePipeline->addMany($middlewares);
    }

    /**
     * Set middleware callback (backward compatibility).
     * 
     * @deprecated Use addMiddleware() instead
     * @param callable(Request, callable): Response $middleware
     */
    public function setMiddleware(callable $middleware): void
    {
        $this->middlewarePipeline->clear();
        $this->middlewarePipeline->add($middleware);
    }

    /**
     * Get the middleware pipeline.
     */
    public function getMiddlewarePipeline(): MiddlewarePipeline
    {
        return $this->middlewarePipeline;
    }

    /**
     * Handle a request and return a response.
     */
    public function handle(Request $request): Response
    {
        $httpMethod = $request->getMethod();
        $uri = $request->getPath();

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case FastRouteDispatcher::NOT_FOUND:
                return Response::json(['error' => 'Not Found'], 404);

            case FastRouteDispatcher::METHOD_NOT_ALLOWED:
                return Response::json(['error' => 'Method Not Allowed'], 405);

            case FastRouteDispatcher::FOUND:
                return $this->handleFound($routeInfo, $request);

            default:
                return Response::json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Handle a found route.
     *
     * @param array<int, mixed> $routeInfo
     */
    private function handleFound(array $routeInfo, Request $request): Response
    {
        $handler = $routeInfo[1] ?? null;
        $vars = $routeInfo[2] ?? [];

        if (!is_array($handler) || count($handler) !== 2) {
            return Response::json(['error' => 'Invalid route handler'], 500);
        }

        [$controllerClass, $method] = $handler;

        try {
            // Execute middleware pipeline
            $finalHandler = function (Request $req) use ($controllerClass, $method, $vars): Response {
                return $this->executeController($controllerClass, $method, $vars, $req);
            };

            return $this->middlewarePipeline->execute($request, $finalHandler);

        } catch (\Throwable $e) {
            // Log error in production
            $errorMessage = 'Internal Server Error';
            if (getenv('APP_ENV') !== 'production') {
                $errorMessage = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            }

            return Response::json([
                'error' => $errorMessage,
                'trace' => getenv('APP_ENV') !== 'production' ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Execute controller method.
     */
    private function executeController(string $controllerClass, string $method, array $vars, Request $request): Response
    {
        $controller = $this->resolver->resolve($controllerClass);
        $result = $this->resolver->call($controller, $method, $vars);

        // Handle different return types
        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof \Psr\Http\Message\ResponseInterface) {
            // Convert PSR-7 response to MAAF Response
            return new Response(
                $result->getStatusCode(),
                $result->getHeaders(),
                (string) $result->getBody()
            );
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        if (is_string($result)) {
            return Response::text($result);
        }

        return Response::json(['error' => 'Invalid controller response'], 500);
    }
}

