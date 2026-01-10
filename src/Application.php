<?php

declare(strict_types=1);

namespace MAAF\Core;

use MAAF\Core\Config\Config;
use MAAF\Core\Config\ConfigInterface;
use MAAF\Core\Container\Container;
use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\EventBus\EventBus;
use MAAF\Core\EventBus\EventBusInterface;
use MAAF\Core\Http\Kernel;
use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;
use MAAF\Core\ModuleLoader\ModuleLoader;
use MAAF\Core\Routing\Router;

/**
 * Application
 * 
 * Fő alkalmazás osztály, amely összekapcsolja az összes komponenst.
 * 
 * @version 1.0.0
 */
final class Application
{
    private ContainerInterface $container;
    private ConfigInterface $config;
    private Router $router;
    private Kernel $kernel;
    private ?ModuleLoader $moduleLoader = null;
    private ?EventBusInterface $eventBus = null;

    public function __construct(string $basePath)
    {
        // Initialize container
        $this->container = Container::fromDefinitions([]);
        
        // Initialize config
        $this->config = new Config();
        
        // Load application config
        $configFile = $basePath . '/config/maaf.php';
        if (file_exists($configFile)) {
            $this->config->loadFromFile($configFile);
        }

        // Initialize router
        $this->router = new Router();
        $this->container->set(Router::class, $this->router);

        // Initialize event bus
        $this->eventBus = new EventBus();
        $this->container->set(EventBusInterface::class, $this->eventBus);

        // Initialize kernel
        $this->kernel = new Kernel($this->container, $this->router);
        $this->container->set(Kernel::class, $this->kernel);

        // Load modules
        $modulesPath = $this->config->get('modules.path');
        $modulesNamespace = $this->config->get('modules.namespace');
        
        if ($modulesPath && $modulesNamespace) {
            $this->moduleLoader = new ModuleLoader($this->container, $this->router);
            $this->moduleLoader->loadModules($modulesPath, $modulesNamespace);
        }

        // Load routes from config
        $routesFile = $this->config->get('routes');
        if ($routesFile && file_exists($routesFile)) {
            $this->loadRoutes($routesFile);
        }
    }

    /**
     * Add middleware
     * 
     * @param MiddlewareInterface $middleware Middleware instance
     * @return void
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->kernel->addMiddleware($middleware);
    }

    /**
     * Run the application
     * 
     * @return void
     */
    public function run(): void
    {
        $request = Request::fromGlobals();
        $response = $this->kernel->handle($request);
        $response->send();
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
     * Get config
     * 
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
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
     * Get event bus
     * 
     * @return EventBusInterface|null
     */
    public function getEventBus(): ?EventBusInterface
    {
        return $this->eventBus;
    }

    /**
     * Load routes from file
     * 
     * @param string $routesFile Path to routes file
     * @return void
     */
    private function loadRoutes(string $routesFile): void
    {
        if (!file_exists($routesFile)) {
            return;
        }

        $routes = require $routesFile;
        
        if (is_callable($routes)) {
            $routes($this->router);
        }
    }
}
