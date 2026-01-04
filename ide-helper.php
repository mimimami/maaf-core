<?php

/**
 * IDE Helper File for MAAF Core
 * 
 * This file provides IDE autocomplete and type hints for MAAF Core.
 * Include this file in your IDE's "Include Path" or use it as a stub.
 * 
 * Usage:
 * - PhpStorm: Add to "Settings > Languages & Frameworks > PHP > Include Path"
 * - VS Code: Use PHP Intelephense or similar extension
 */

namespace MAAF\Core {

    /**
     * Application - Main application class
     * 
     * @method self bootstrap() Bootstrap the application
     * @method void run(\MAAF\Core\Http\Request|null $request = null) Run the application
     * @method \MAAF\Core\Http\Response handle(\MAAF\Core\Http\Request|null $request = null) Handle a request
     * @method self addMiddleware(\MAAF\Core\Http\MiddlewareInterface|callable|string $middleware) Add middleware
     * @method self addMiddlewares(array $middlewares) Add multiple middlewares
     * @method \Psr\Container\ContainerInterface getContainer() Get DI container
     * @method \MAAF\Core\Http\HttpKernel getKernel() Get HTTP kernel
     * @method \MAAF\Core\Routing\Router getRouter() Get router
     * @method \MAAF\Core\Module\ModuleLoader getLoader() Get module loader
     * @method string getBasePath() Get base path
     * @method array getConfig() Get configuration
     */
    class Application
    {
        /**
         * @param string $basePath Base path of the application
         * @param array|null $config Optional configuration array
         */
        public function __construct(string $basePath, ?array $config = null) {}
    }
}

namespace MAAF\Core\Http {

    /**
     * Request - HTTP request handling
     * 
     * @method string getPath() Get request path
     * @method string getMethod() Get HTTP method (GET, POST, etc.)
     * @method array getBody() Get request body as array
     * @method mixed getBodyValue(string $key, mixed $default = null) Get body value
     * @method mixed getQuery(string $key, mixed $default = null) Get query parameter
     * @method string|null getHeader(string $name) Get header value
     * @method array getHeaders() Get all headers
     * @method static self fromGlobals() Create request from globals
     */
    class Request
    {
        public array $query = [];
        public array $post = [];
        public array $server = [];
        public array $headers = [];
        public array $attributes = [];
    }

    /**
     * Response - HTTP response handling
     * 
     * @method static self json(array|object $data, int $statusCode = 200) Create JSON response
     * @method static self text(string $text, int $statusCode = 200) Create text response
     * @method static self html(string $html, int $statusCode = 200) Create HTML response
     * @method static self empty(int $statusCode = 204) Create empty response
     * @method void send() Send response to client
     * @method int getStatusCode() Get status code
     * @method self withHeader(string $name, string|array $value) Add header
     */
    class Response
    {
        public static function json(array|object $data, int $statusCode = 200): self {}
        public static function text(string $text, int $statusCode = 200): self {}
        public static function html(string $html, int $statusCode = 200): self {}
        public static function empty(int $statusCode = 204): self {}
        public function send(): void {}
        public function getStatusCode(): int {}
        public function withHeader(string $name, string|array $value): self {}
    }

    /**
     * HttpKernel - HTTP kernel
     * 
     * @method \MAAF\Core\Http\Response handle(\MAAF\Core\Http\Request $request) Handle request
     * @method self addMiddleware(\MAAF\Core\Http\MiddlewareInterface|callable $middleware) Add middleware
     * @method self addMiddlewares(array $middlewares) Add multiple middlewares
     */
    class HttpKernel
    {
        public function handle(Request $request): Response {}
        public function addMiddleware(MiddlewareInterface|callable $middleware): self {}
        public function addMiddlewares(array $middlewares): self {}
    }

    /**
     * MiddlewareInterface - Middleware interface
     */
    interface MiddlewareInterface
    {
        public function handle(Request $request, callable $next): Response;
    }
}

namespace MAAF\Core\Module {

    /**
     * ModuleLoader - Module loader
     * 
     * @method self discover() Discover modules
     * @method self registerServices(\DI\ContainerBuilder $builder) Register module services
     * @method self registerRoutes(\MAAF\Core\Routing\Router|callable $router) Register module routes
     */
    class ModuleLoader
    {
        public function discover(): void {}
        public function registerServices(\DI\ContainerBuilder $builder): void {}
        public function registerRoutes($router): void {}
    }

    /**
     * ModuleRegistry - Module registry
     * 
     * @method void register(string $name, \MAAF\Core\Module\ModuleMetadata $metadata) Register module
     * @method \MAAF\Core\Module\ModuleMetadata|null get(string $name) Get module
     * @method bool has(string $name) Check if module exists
     * @method array all() Get all modules
     */
    class ModuleRegistry
    {
        public function register(string $name, ModuleMetadata $metadata): void {}
        public function get(string $name): ?ModuleMetadata {}
        public function has(string $name): bool {}
        public function all(): array {}
    }
}

namespace MAAF\Core\Routing {

    /**
     * Router - Router
     * 
     * @method self addRoute(string $method, string $path, array $handler) Add route
     * @method self loadRoutesFromFile(string $file) Load routes from file
     * @method \FastRoute\Dispatcher buildDispatcher() Build dispatcher
     */
    class Router
    {
        public function addRoute(string $method, string $path, array $handler): self {}
        public function loadRoutesFromFile(string $file): void {}
        public function buildDispatcher(): \FastRoute\Dispatcher {}
    }
}

