<?php

declare(strict_types=1);

namespace MAAF\Core\Testing;

use DI\ContainerBuilder;
use MAAF\Core\Container\Container;
use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\EventBus\EventBusInterface;
use MAAF\Core\ModuleLoader\ModuleInterface;
use MAAF\Core\ModuleLoader\ModuleLoader;
use MAAF\Core\Routing\Router;

/**
 * Module Test Helper
 * 
 * Segédeszközök modulok teszteléséhez.
 * 
 * @version 1.0.0
 */
final class ModuleTestHelper
{
    private ContainerInterface $container;
    private Router $router;
    private ModuleLoader $moduleLoader;
    private ?EventBusInterface $eventBus = null;

    /**
     * @var array<string, ModuleInterface>
     */
    private array $loadedModules = [];

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container ?? Container::fromDefinitions([]);
        $this->router = new Router();
        $this->moduleLoader = new ModuleLoader($this->container, $this->router);

        if ($this->container->has(EventBusInterface::class)) {
            $this->eventBus = $this->container->get(EventBusInterface::class);
        }
    }

    /**
     * Load a module for testing
     * 
     * @param string $moduleClass Module class name
     * @param string $moduleName Module name
     * @return self
     */
    public function loadModule(string $moduleClass, string $moduleName): self
    {
        $this->moduleLoader->loadModule($moduleClass, $moduleName);
        $this->loadedModules[$moduleName] = new $moduleClass();
        
        return $this;
    }

    /**
     * Register services for testing
     * 
     * @param array<string, mixed>|callable|string $definitions Service definitions
     * @return self
     */
    public function registerServices(array|callable|string $definitions): self
    {
        // Create new container with definitions
        $newContainer = Container::fromDefinitions($definitions);
        
        // Replace container
        $this->container = $newContainer;
        
        // Recreate module loader with new container
        $this->moduleLoader = new ModuleLoader($this->container, $this->router);
        
        // Recreate event bus reference if available
        if ($this->container->has(EventBusInterface::class)) {
            $this->eventBus = $this->container->get(EventBusInterface::class);
        }
        
        return $this;
    }

    /**
     * Get container
     * 
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get router
     * 
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get module loader
     * 
     * @return ModuleLoader
     */
    public function getModuleLoader(): ModuleLoader
    {
        return $this->moduleLoader;
    }

    /**
     * Get event bus
     * 
     * @return EventBusInterface|null
     */
    public function getEventBus(): ?EventBusInterface
    {
        return $this->eventBus;
    }

    /**
     * Get loaded modules
     * 
     * @return array<string, ModuleInterface>
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * Check if module is loaded
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function hasModule(string $moduleName): bool
    {
        return isset($this->loadedModules[$moduleName]);
    }

    /**
     * Get routes registered by modules
     * 
     * @return array<int, array{method: string, route: string, handler: callable|array}>
     */
    public function getRoutes(): array
    {
        return $this->router->getRoutes();
    }

    /**
     * Find route by pattern
     * 
     * @param string $method HTTP method
     * @param string $pattern Route pattern
     * @return array{method: string, route: string, handler: callable|array}|null
     */
    public function findRoute(string $method, string $pattern): ?array
    {
        foreach ($this->router->getRoutes() as $route) {
            if ($route['method'] === strtoupper($method) && $route['route'] === $pattern) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Assert module is loaded
     * 
     * @param string $moduleName Module name
     * @return void
     */
    public function assertModuleLoaded(string $moduleName): void
    {
        if (!$this->hasModule($moduleName)) {
            throw new \RuntimeException("Module '{$moduleName}' is not loaded");
        }
    }

    /**
     * Assert route exists
     * 
     * @param string $method HTTP method
     * @param string $pattern Route pattern
     * @return void
     */
    public function assertRouteExists(string $method, string $pattern): void
    {
        $route = $this->findRoute($method, $pattern);
        
        if ($route === null) {
            throw new \RuntimeException("Route '{$method} {$pattern}' not found");
        }
    }

    /**
     * Reset helper state
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->container = Container::fromDefinitions([]);
        $this->router = new Router();
        $this->moduleLoader = new ModuleLoader($this->container, $this->router);
        $this->loadedModules = [];

        return $this;
    }
}
