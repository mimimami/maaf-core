<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

use Psr\Container\ContainerInterface;

/**
 * Controller Resolver
 * 
 * Resolves controllers from the DI container.
 */
final class ControllerResolver
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * Resolve a controller instance from the container.
     *
     * @param class-string $controllerClass
     */
    public function resolve(string $controllerClass): object
    {
        if (!$this->container->has($controllerClass)) {
            throw new \RuntimeException("Controller not found: {$controllerClass}");
        }

        return $this->container->get($controllerClass);
    }

    /**
     * Call a controller method with parameters.
     *
     * @param object $controller
     * @param string $method
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function call(object $controller, string $method, array $params = []): mixed
    {
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException(
                "Method not found: " . get_class($controller) . "::{$method}"
            );
        }

        return $controller->{$method}(...array_values($params));
    }
}

