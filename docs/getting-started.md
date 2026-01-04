# MAAF Core - Használati Útmutató

## Telepítés

### Composer-rel

```bash
composer require maaf/core
```

Vagy ha helyi fejlesztésről van szó (SmartLearning projektben):

```bash
composer require maaf/core:dev-main
```

## Alapvető Használat

### 1. Projekt Struktúra

Hozz létre egy projektet a következő struktúrával:

```
my-project/
├── composer.json
├── config/
│   ├── services.php      # DI szolgáltatások
│   └── routes.php         # Route definíciók
├── public/
│   └── index.php          # Front controller
├── src/
│   └── Modules/           # Modulok
│       └── MyModule/
│           ├── Module.php
│           ├── Controllers/
│           ├── Services/
│           └── Repositories/
└── vendor/
```

### 2. Front Controller (public/index.php)

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use MAAF\Core\Module\ModuleRegistry;
use MAAF\Core\Module\ModuleLoader;
use MAAF\Core\Routing\Router;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;
use MAAF\Core\Http\HttpKernel;
use MAAF\Core\Http\ControllerResolver;

$basePath = dirname(__DIR__);

// 1. Build DI container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/services.php');

// 2. Module discovery
$registry = new ModuleRegistry();
$loader = new ModuleLoader(
    $basePath . '/src/Modules',
    $registry,
    'App\\Modules' // namespace prefix
);
$loader->discover();
$loader->registerServices($containerBuilder);

// Build container
$container = $containerBuilder->build();

// 3. Router setup
$router = new Router(__DIR__ . '/../config/routes.php');
$loader->registerRoutes($router);

// 4. HTTP Kernel setup
$resolver = new ControllerResolver($container);
$kernel = new HttpKernel($router, $resolver, $container);

// 5. Handle request
$request = Request::fromGlobals();
$response = $kernel->handle($request);
$response->send();
```

### 3. DI Szolgáltatások (config/services.php)

```php
<?php

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // PDO példa
        PDO::class => function () {
            return new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
        },
        
        // Egyéb szolgáltatások...
    ]);
};
```

### 4. Route Definíciók (config/routes.php)

```php
<?php

return [
    // GET /hello
    ['GET', '/hello', [\App\Modules\MyModule\Controllers\HelloController::class, 'index']],
    
    // POST /api/users
    ['POST', '/api/users', [\App\Modules\MyModule\Controllers\UserController::class, 'create']],
];
```

### 5. Modul Létrehozása

#### Modul Struktúra

```
src/Modules/MyModule/
├── Module.php
├── Controllers/
│   └── HelloController.php
├── Services/
│   └── HelloService.php
└── Repositories/
    └── HelloRepository.php
```

#### Module.php

```php
<?php

declare(strict_types=1);

namespace App\Modules\MyModule;

use DI\ContainerBuilder;
use MAAF\Core\Routing\Router;

final class Module
{
    public static function registerServices(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            \App\Modules\MyModule\Services\HelloService::class => \DI\create(),
        ]);
    }

    public static function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/hello', [
            \App\Modules\MyModule\Controllers\HelloController::class,
            'index'
        ]);
    }
}
```

#### Controller

```php
<?php

declare(strict_types=1);

namespace App\Modules\MyModule\Controllers;

use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

final class HelloController
{
    public function index(Request $request): Response
    {
        return Response::json([
            'message' => 'Hello, MAAF Core!'
        ]);
    }
}
```

## Middleware Használata

### Middleware Hozzáadása

```php
use MAAF\Core\Http\Middleware\LoggingMiddleware;
use MAAF\Core\Http\Middleware\CorsMiddleware;

$kernel = new HttpKernel($router, $resolver, $container);

// Middleware-ek hozzáadása
$kernel->addMiddleware(new LoggingMiddleware());
$kernel->addMiddleware(new CorsMiddleware(['*']));
```

### Custom Middleware Létrehozása

#### Interface alapú

```php
<?php

namespace App\Core\Middleware;

use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->getHeader('Authorization');
        
        if (!$token) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        // Validate token...
        
        return $next($request);
    }
}
```

#### Callable middleware

```php
$kernel->addMiddleware(function (Request $request, callable $next): Response {
    // Before controller
    $response = $next($request);
    // After controller
    return $response->withHeader('X-Custom-Header', 'value');
});
```

## Request és Response Használata

### Request

```php
// Request létrehozása
$request = Request::fromGlobals();

// Path lekérdezése
$path = $request->getPath(); // '/api/users'

// HTTP metódus
$method = $request->getMethod(); // 'GET', 'POST', etc.

// Query paraméterek
$id = $request->getQuery('id'); // ?id=123

// Request body (JSON vagy form-data)
$data = $request->getBody(); // ['name' => 'John']
$name = $request->getBodyValue('name'); // 'John'

// Headers
$auth = $request->getHeader('Authorization');
```

### Response

```php
// JSON válasz
return Response::json(['status' => 'ok'], 200);

// Text válasz
return Response::text('Hello World', 200);

// HTML válasz
return Response::html('<h1>Hello</h1>', 200);

// Üres válasz
return Response::empty(204);

// Header hozzáadása
$response = Response::json(['data' => 'value']);
return $response->withHeader('X-Custom-Header', 'value');
```

## UseCase Architektúra (Opcionális)

A MAAF Core támogatja a UseCase architektúrát is:

```
src/Modules/MyModule/
├── Application/
│   └── UseCase/
│       └── CreateUser/
│           ├── CreateUserHandler.php
│           ├── CreateUserInput.php
│           └── CreateUserOutput.php
├── Domain/
│   ├── Model/
│   │   └── User.php
│   └── Repository/
│       └── UserRepositoryPort.php
└── Infrastructure/
    ├── Http/
    │   └── CreateUserController.php
    └── Repository/
        └── UserRepositoryAdapter.php
```

## Példa Projekt

### Teljes példa: Hello World API

**public/index.php:**
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use MAAF\Core\Module\ModuleRegistry;
use MAAF\Core\Module\ModuleLoader;
use MAAF\Core\Routing\Router;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\HttpKernel;
use MAAF\Core\Http\ControllerResolver;

$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

$registry = new ModuleRegistry();
$loader = new ModuleLoader(__DIR__ . '/../src/Modules', $registry, 'App\\Modules');
$loader->discover();
$loader->registerServices($containerBuilder);
$container = $containerBuilder->build();

$router = new Router();
$loader->registerRoutes($router);

$kernel = new HttpKernel($router, new ControllerResolver($container), $container);
$response = $kernel->handle(Request::fromGlobals());
$response->send();
```

**src/Modules/Hello/Module.php:**
```php
<?php
namespace App\Modules\Hello;

use MAAF\Core\Routing\Router;

final class Module
{
    public static function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/hello', [
            \App\Modules\Hello\Controllers\HelloController::class,
            'index'
        ]);
    }
}
```

**src/Modules/Hello/Controllers/HelloController.php:**
```php
<?php
namespace App\Modules\Hello\Controllers;

use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

final class HelloController
{
    public function index(Request $request): Response
    {
        return Response::json(['message' => 'Hello, MAAF Core!']);
    }
}
```

## További Dokumentáció

- [Middleware Pipeline](middleware-pipeline.md) - Middleware pipeline részletes útmutató
- [README.md](../README.md) - Alapvető információk

## Következő Lépések

1. ✅ MAAF Core telepítése
2. ✅ Projekt struktúra létrehozása
3. ✅ Első modul létrehozása
4. ✅ Controller implementálása
5. ✅ Middleware-ek hozzáadása
6. ✅ Route-ok definiálása
7. ✅ Tesztelés

