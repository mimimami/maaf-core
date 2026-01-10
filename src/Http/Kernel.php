<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\Routing\Router;

/**
 * HTTP Kernel 1.0
 * 
 * Stabil HTTP kernel, amely kezeli a request-response ciklust.
 * 
 * @version 1.0.0
 */
final class Kernel
{
    /**
     * @var array<int, MiddlewareInterface>
     */
    private array $middleware = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly Router $router
    ) {
    }

    /**
     * Add middleware
     * 
     * @param MiddlewareInterface $middleware Middleware instance
     * @return void
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Handle HTTP request
     * 
     * @param Request $request HTTP request
     * @return Response HTTP response
     */
    public function handle(Request $request): Response
    {
        $middlewareStack = $this->buildMiddlewareStack($request);
        return $middlewareStack($request);
    }

    /**
     * Build middleware stack
     * 
     * @param Request $request HTTP request
     * @return callable
     */
    private function buildMiddlewareStack(Request $request): callable
    {
        $stack = function (Request $request): Response {
            return $this->dispatchRoute($request);
        };

        // Apply middleware in reverse order (last added = first executed)
        foreach (array_reverse($this->middleware) as $middleware) {
            $stack = function (Request $request) use ($middleware, $stack): Response {
                return $middleware->handle($request, $stack);
            };
        }

        return $stack;
    }

    /**
     * Dispatch route
     * 
     * @param Request $request HTTP request
     * @return Response HTTP response
     */
    private function dispatchRoute(Request $request): Response
    {
        $uri = $request->getPath();
        $method = $request->getMethod();

        $routeInfo = $this->router->dispatch($method, $uri);

        switch ($routeInfo['status']) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                return Response::json(['error' => 'Not Found'], 404);

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                return Response::json(['error' => 'Method Not Allowed'], 405);

            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo['handler'];
                $params = $routeInfo['params'] ?? [];

                return $this->callHandler($handler, $request, $params);
        }

        return Response::json(['error' => 'Internal Server Error'], 500);
    }

    /**
     * Call route handler
     * 
     * @param callable|array $handler Route handler
     * @param Request $request HTTP request
     * @param array<string, string> $params Route parameters
     * @return Response HTTP response
     */
    private function callHandler(callable|array $handler, Request $request, array $params): Response
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            
            if ($this->container->has($class)) {
                $instance = $this->container->get($class);
            } else {
                $instance = $this->container->make($class);
            }

            if (!method_exists($instance, $method)) {
                return Response::json(['error' => 'Handler method not found'], 500);
            }

            // Inject request and route params
            $reflection = new \ReflectionMethod($instance, $method);
            $arguments = $this->resolveArguments($reflection, $request, $params);

            $result = $instance->$method(...$arguments);

            if ($result instanceof Response) {
                return $result;
            }

            return Response::json(['error' => 'Invalid handler response'], 500);
        }

        if (is_callable($handler)) {
            $result = $handler($request, $params);
            
            if ($result instanceof Response) {
                return $result;
            }

            return Response::json(['error' => 'Invalid handler response'], 500);
        }

        return Response::json(['error' => 'Invalid handler'], 500);
    }

    /**
     * Resolve method arguments using dependency injection
     * 
     * @param \ReflectionMethod $reflection Method reflection
     * @param Request $request HTTP request
     * @param array<string, string> $params Route parameters
     * @return array<int, mixed>
     */
    private function resolveArguments(\ReflectionMethod $reflection, Request $request, array $params): array
    {
        $arguments = [];

        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();

                if ($typeName === Request::class) {
                    $arguments[] = $request;
                    continue;
                }

                if ($typeName === Response::class) {
                    // Response is typically returned, not injected
                    continue;
                }

                // Check if param name matches route param
                if (isset($params[$param->getName()])) {
                    $arguments[] = $params[$param->getName()];
                    continue;
                }

                // Try to resolve from container
                if ($this->container->has($typeName)) {
                    $arguments[] = $this->container->get($typeName);
                    continue;
                }
            }

            // Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            }
        }

        return $arguments;
    }
}
