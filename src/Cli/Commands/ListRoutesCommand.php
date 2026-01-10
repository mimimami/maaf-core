<?php

declare(strict_types=1);

namespace MAAF\Core\Cli\Commands;

use MAAF\Core\Cli\CommandInterface;
use MAAF\Core\Routing\Router;

/**
 * List Routes Command
 * 
 * Lists all registered routes.
 * 
 * @version 1.0.0
 */
final class ListRoutesCommand implements CommandInterface
{
    public function __construct(
        private readonly Router $router
    ) {
    }

    public function getName(): string
    {
        return 'route:list';
    }

    public function getDescription(): string
    {
        return 'List all registered routes';
    }

    public function execute(array $args): int
    {
        $routes = $this->router->getRoutes();

        if (empty($routes)) {
            echo "No routes registered.\n";
            return 0;
        }

        echo "Registered Routes:\n";
        echo "==================\n\n";
        printf("%-8s %-30s %s\n", "Method", "Route", "Handler");
        echo str_repeat("-", 80) . "\n";

        foreach ($routes as $route) {
            $method = $route['method'];
            $routePath = $route['route'];
            $handler = $this->formatHandler($route['handler']);
            
            printf("%-8s %-30s %s\n", $method, $routePath, $handler);
        }

        echo "\n";
        return 0;
    }

    /**
     * Format handler for display
     * 
     * @param callable|array $handler Route handler
     * @return string
     */
    private function formatHandler(callable|array $handler): string
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $className = is_string($class) ? $class : get_class($class);
            return "{$className}::{$method}";
        }

        if (is_callable($handler)) {
            if (is_string($handler)) {
                return $handler;
            }

            if (is_object($handler)) {
                return get_class($handler);
            }
        }

        return 'Unknown';
    }
}
