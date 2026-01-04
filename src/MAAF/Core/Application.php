<?php

declare(strict_types=1);

namespace MAAF\Core;

use DI\ContainerBuilder;
use MAAF\Core\Module\ModuleRegistry;
use MAAF\Core\Module\ModuleLoader;
use MAAF\Core\Routing\Router;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;
use MAAF\Core\Http\HttpKernel;
use MAAF\Core\Http\ControllerResolver;
use MAAF\Core\Http\MiddlewareInterface;
use Psr\Container\ContainerInterface;

/**
 * Application
 * 
 * Main application class that bootstraps and runs the MAAF Core framework.
 * Provides a simple, convention-based API while maintaining flexibility.
 */
final class Application
{
    private string $basePath;
    private array $config;
    private ?ContainerInterface $container = null;
    private ?HttpKernel $kernel = null;
    private ?ModuleLoader $loader = null;
    private ?Router $router = null;
    private bool $bootstrapped = false;

    /**
     * Create a new Application instance.
     * 
     * @param string $basePath Base path of the application
     * @param array|null $config Optional configuration array
     */
    public function __construct(string $basePath, ?array $config = null)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->config = $config ?? $this->loadDefaultConfig();
    }

    /**
     * Load default configuration from config file or use defaults.
     * 
     * @return array<string, mixed>
     */
    private function loadDefaultConfig(): array
    {
        $configFile = $this->basePath . '/config/maaf.php';

        if (file_exists($configFile)) {
            $config = require $configFile;
            if (is_array($config)) {
                return $this->mergeWithDefaults($config);
            }
        }

        return $this->getDefaultConfig();
    }

    /**
     * Get default configuration.
     * 
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'modules' => [
                'path' => $this->basePath . '/src/Modules',
                'namespace' => 'App\\Modules',
            ],
            'services' => $this->basePath . '/config/services.php',
            'routes' => $this->basePath . '/config/routes.php',
            'middleware' => [],
        ];
    }

    /**
     * Merge user config with defaults.
     * 
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function mergeWithDefaults(array $config): array
    {
        $defaults = $this->getDefaultConfig();

        // Deep merge modules config
        if (isset($config['modules'])) {
            $config['modules'] = array_merge($defaults['modules'], $config['modules']);
        }

        return array_merge($defaults, $config);
    }

    /**
     * Bootstrap the application.
     * 
     * @return self
     */
    public function bootstrap(): self
    {
        if ($this->bootstrapped) {
            return $this;
        }

        // 1. Build DI container
        $containerBuilder = new ContainerBuilder();
        
        $servicesFile = $this->config['services'] ?? null;
        if ($servicesFile && file_exists($servicesFile)) {
            $containerBuilder->addDefinitions($servicesFile);
        }

        // 2. Module discovery
        $registry = new ModuleRegistry();
        $modulesPath = $this->config['modules']['path'] ?? ($this->basePath . '/src/Modules');
        $modulesNamespace = $this->config['modules']['namespace'] ?? 'App\\Modules';
        
        $this->loader = new ModuleLoader(
            $modulesPath,
            $registry,
            $modulesNamespace
        );
        $this->loader->discover();

        // Register module services
        $this->loader->registerServices($containerBuilder);

        // Build container
        $this->container = $containerBuilder->build();

        // 3. Router setup
        $this->router = new Router();
        
        $routesFile = $this->config['routes'] ?? null;
        if ($routesFile && file_exists($routesFile)) {
            $this->router->loadRoutesFromFile($routesFile);
        }

        // Register module routes
        $this->loader->registerRoutes($this->router);

        // 4. HTTP Kernel setup
        $resolver = new ControllerResolver($this->container);
        $this->kernel = new HttpKernel($this->router, $resolver, $this->container);

        // 5. Register middleware
        $middlewares = $this->config['middleware'] ?? [];
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }

        $this->bootstrapped = true;

        return $this;
    }

    /**
     * Add a middleware to the pipeline.
     * 
     * @param MiddlewareInterface|callable|string $middleware Middleware instance, callable, or class name
     * @return self
     */
    public function addMiddleware(MiddlewareInterface|callable|string $middleware): self
    {
        if (!$this->bootstrapped) {
            $this->bootstrap();
        }

        // If string, resolve from container
        if (is_string($middleware)) {
            if ($this->container && $this->container->has($middleware)) {
                $middleware = $this->container->get($middleware);
            } elseif (class_exists($middleware)) {
                $middleware = new $middleware();
            }
        }

        $this->kernel->addMiddleware($middleware);

        return $this;
    }

    /**
     * Add multiple middlewares to the pipeline.
     * 
     * @param array<int, MiddlewareInterface|callable|string> $middlewares
     * @return self
     */
    public function addMiddlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }

        return $this;
    }

    /**
     * Handle a request and return a response.
     * 
     * @param Request|null $request Optional request (uses fromGlobals if null)
     * @return Response
     */
    public function handle(?Request $request = null): Response
    {
        if (!$this->bootstrapped) {
            $this->bootstrap();
        }

        $request = $request ?? Request::fromGlobals();
        return $this->kernel->handle($request);
    }

    /**
     * Run the application (handle request and send response).
     * 
     * @param Request|null $request Optional request (uses fromGlobals if null)
     * @return void
     */
    public function run(?Request $request = null): void
    {
        $response = $this->handle($request);
        $response->send();
    }

    /**
     * Get the DI container.
     * 
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        if (!$this->bootstrapped) {
            $this->bootstrap();
        }

        return $this->container;
    }

    /**
     * Get the HTTP kernel.
     * 
     * @return HttpKernel
     */
    public function getKernel(): HttpKernel
    {
        if (!$this->bootstrapped) {
            $this->bootstrap();
        }

        return $this->kernel;
    }

    /**
     * Get the router.
     * 
     * @return Router
     */
    public function getRouter(): Router
    {
        if (!$this->bootstrapped) {
            $this->bootstrap();
        }

        return $this->router;
    }

    /**
     * Get the module loader.
     * 
     * @return ModuleLoader
     */
    public function getLoader(): ModuleLoader
    {
        if (!$this->bootstrapped) {
            $this->bootstrap();
        }

        return $this->loader;
    }

    /**
     * Get the base path.
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the configuration.
     * 
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}

