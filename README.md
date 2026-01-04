# MAAF Core

Modular Application Architecture Framework - Core Components

## Installation

```bash
composer require maaf/core
```

## Automatic Packagist Updates

This package uses **GitHub Actions** to automatically update Packagist when you push changes.

The workflow is already configured in `.github/workflows/packagist-update.yml`.

**No webhook setup needed!** Just push to the `main` branch and Packagist will be updated automatically.

See `PUBLISHING.md` for detailed instructions.

## Requirements

- PHP >= 8.1
- php-di/php-di ^6.4
- nikic/fast-route ^1.3
- psr/http-message ^2.0
- psr/container ^2.0

## Components

### Application

- **Application** - Main application class for easy bootstrap and configuration

### Module System

- **ModuleRegistry** - Stores metadata about discovered modules
- **ModuleMetadata** - Module information container
- **ModuleLoader** - Automatically discovers and registers modules

### HTTP Layer

- **Request** - HTTP request handling with body parsing
- **Response** - PSR-7 compatible HTTP response
- **HttpKernel** - Request/response handling kernel
- **ControllerResolver** - Resolves controllers from DI container

### Routing

- **Router** - Route registration and dispatching

### DI

- **ContainerFactory** - Helper for creating DI containers

## Quick Start

### Simple Way (Recommended)

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->run();
```

### Advanced Way (Full Control)

```php
<?php

use MAAF\Core\Module\ModuleRegistry;
use MAAF\Core\Module\ModuleLoader;
use MAAF\Core\Routing\Router;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\HttpKernel;
use MAAF\Core\Http\ControllerResolver;
use DI\ContainerBuilder;

// 1. Build DI container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions('config/services.php');
$container = $containerBuilder->build();

// 2. Discover modules
$registry = new ModuleRegistry();
$loader = new ModuleLoader(
    __DIR__ . '/src/Modules',
    $registry,
    'App\\Modules' // namespace prefix
);
$loader->discover();
$loader->registerServices($containerBuilder);
$container = $containerBuilder->build();

// 3. Setup router
$router = new Router('config/routes.php');
$loader->registerRoutes($router);
$dispatcher = $router->buildDispatcher();

// 4. Handle request
$resolver = new ControllerResolver($container);
// HttpKernel accepts Router (as per MAAF book) or Dispatcher
$kernel = new HttpKernel($router, $resolver, $container);

$request = Request::fromGlobals();
$response = $kernel->handle($request);
$response->send();
```

## Module Structure

Create a module with the following structure:

```
src/Modules/MyModule/
├── Module.php          # Module registration
├── Controllers/        # HTTP controllers
├── Services/           # Business logic
└── Repositories/       # Data access
```

**Module.php:**

```php
<?php
namespace App\Modules\MyModule;

use DI\ContainerBuilder;
use MAAF\Core\Routing\Router;

final class Module
{
    public static function registerServices(ContainerBuilder $builder): void
    {
        // Register services
    }

    public static function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/my-module', [
            MyController::class,
            'index'
        ]);
    }
}
```

## Middleware Pipeline

MAAF Core supports a full middleware pipeline system. You can chain multiple middlewares together.

### Using MiddlewareInterface

```php
use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

class MyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Before controller execution
        // ... your logic ...
        
        $response = $next($request);
        
        // After controller execution
        // ... your logic ...
        
        return $response;
    }
}
```

### Using Callable Middleware

```php
$middleware = function (Request $request, callable $next): Response {
    // Before controller
    $response = $next($request);
    // After controller
    return $response;
};
```

### Adding Middlewares to HttpKernel

```php
// Single middleware
$kernel->addMiddleware(new LoggingMiddleware());

// Multiple middlewares
$kernel->addMiddlewares([
    new CorsMiddleware(['*']),
    new LoggingMiddleware(),
    new RateLimitingMiddleware(100, 60),
]);

// Or using callables
$kernel->addMiddleware(function (Request $request, callable $next): Response {
    // Custom logic
    return $next($request);
});
```

### Middleware Execution Order

Middlewares are executed in the order they are added:
1. First middleware receives the request
2. Calls `$next($request)` to pass to next middleware
3. Last middleware calls the controller
4. Response bubbles back through middlewares in reverse order

**Example:**
```php
// Middlewares added in this order:
$kernel->addMiddleware($middleware1);  // Executes first
$kernel->addMiddleware($middleware2);  // Executes second
$kernel->addMiddleware($middleware3);  // Executes third

// Execution flow:
// Request → middleware1 → middleware2 → middleware3 → Controller
// Response ← middleware1 ← middleware2 ← middleware3 ← Controller
```

### Built-in Middlewares

MAAF Core includes example middlewares:

- **LoggingMiddleware** - Logs requests and responses
- **CorsMiddleware** - Handles CORS headers
- **RateLimitingMiddleware** - Implements rate limiting

## Application Class

For easier usage, use the `Application` class:

```php
use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->addMiddleware(new LoggingMiddleware());
$app->run();
```

See [Application Class Documentation](docs/application-class.md) for more details.

## IDE Support

MAAF Core includes IDE support for better autocomplete and type hints:

- **PhpStorm** - `.phpstorm.meta.php` for autocomplete
- **VS Code** - `ide-helper.php` for IntelliSense
- **Code Snippets** - Templates for quick development

See [IDE Support Documentation](docs/ide-support.md) for details.

## License

MIT 
"# Test webhook without token" 
