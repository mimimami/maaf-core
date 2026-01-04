# Application Class - Használati Útmutató

Az `Application` osztály egy egyszerű, konvenció alapú API-t biztosít a MAAF Core használatához.

## Telepítés

Az `Application` osztály a MAAF Core package része, nincs külön telepítés szükséges.

## Alapvető Használat

### Minimális Használat (Konvenció Alapú)

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->run();
```

Ez automatikusan:
- Betölti a `config/services.php` fájlt (ha létezik)
- Felfedezi a modulokat a `src/Modules/` könyvtárban
- Betölti a `config/routes.php` fájlt (ha létezik)
- Regisztrálja a modul route-okat
- Kezeli a request-et és küldi a response-t

### Konfigurációval

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..', [
    'modules' => [
        'path' => __DIR__ . '/../src/Modules',
        'namespace' => 'App\\Modules',
    ],
    'services' => __DIR__ . '/../config/services.php',
    'routes' => __DIR__ . '/../config/routes.php',
    'middleware' => [
        \MAAF\Core\Http\Middleware\LoggingMiddleware::class,
    ],
]);

$app->run();
```

### Konfigurációs Fájl (`config/maaf.php`)

```php
<?php

return [
    'modules' => [
        'path' => __DIR__ . '/../src/Modules',
        'namespace' => 'App\\Modules',
    ],
    'services' => __DIR__ . '/../services.php',
    'routes' => __DIR__ . '/../routes.php',
    'middleware' => [
        \MAAF\Core\Http\Middleware\LoggingMiddleware::class,
        \MAAF\Core\Http\Middleware\CorsMiddleware::class,
    ],
];
```

Használat:

```php
$app = new Application(__DIR__ . '/..');
// Automatikusan betölti a config/maaf.php fájlt
$app->run();
```

## Middleware Hozzáadása

### Egy Middleware

```php
use MAAF\Core\Http\Middleware\LoggingMiddleware;

$app = new Application(__DIR__ . '/..');
$app->addMiddleware(new LoggingMiddleware());
$app->run();
```

### Middleware Class Name

```php
$app = new Application(__DIR__ . '/..');
$app->addMiddleware(\MAAF\Core\Http\Middleware\LoggingMiddleware::class);
$app->run();
```

### Több Middleware

```php
$app = new Application(__DIR__ . '/..');
$app->addMiddlewares([
    new \MAAF\Core\Http\Middleware\LoggingMiddleware(),
    new \MAAF\Core\Http\Middleware\CorsMiddleware(['*']),
    new \MAAF\Core\Http\Middleware\RateLimitingMiddleware(100, 60),
]);
$app->run();
```

### Callable Middleware

```php
$app = new Application(__DIR__ . '/..');
$app->addMiddleware(function (Request $request, callable $next): Response {
    // Before controller
    $response = $next($request);
    // After controller
    return $response->withHeader('X-Custom-Header', 'value');
});
$app->run();
```

## Teljes Kontroll

Ha teljes kontrollt szeretnél:

```php
$app = new Application(__DIR__ . '/..');
$app->bootstrap();

// Custom műveletek
$container = $app->getContainer();
$kernel = $app->getKernel();
$router = $app->getRouter();

// Middleware hozzáadása
$app->addMiddleware(new CustomMiddleware());

// Request kezelés
$app->run();
```

## API Referencia

### Konstruktor

```php
public function __construct(string $basePath, ?array $config = null)
```

- `$basePath` - Az alkalmazás alap könyvtára
- `$config` - Opcionális konfigurációs tömb

### Metódusok

#### `bootstrap(): self`

Bootstrap-otja az alkalmazást. Automatikusan meghívódik, ha szükséges.

```php
$app->bootstrap();
```

#### `run(?Request $request = null): void`

Kezeli a request-et és küldi a response-t.

```php
$app->run();
// vagy
$app->run($customRequest);
```

#### `handle(?Request $request = null): Response`

Kezeli a request-et és visszaadja a response-t (nem küldi el).

```php
$response = $app->handle();
// vagy
$response = $app->handle($customRequest);
```

#### `addMiddleware($middleware): self`

Hozzáad egy middleware-t a pipeline-hoz.

```php
$app->addMiddleware(new LoggingMiddleware());
```

#### `addMiddlewares(array $middlewares): self`

Hozzáad több middleware-t egyszerre.

```php
$app->addMiddlewares([
    new LoggingMiddleware(),
    new CorsMiddleware(),
]);
```

#### `getContainer(): ContainerInterface`

Visszaadja a DI konténert.

```php
$container = $app->getContainer();
$service = $container->get(MyService::class);
```

#### `getKernel(): HttpKernel`

Visszaadja a HTTP Kernel-t.

```php
$kernel = $app->getKernel();
```

#### `getRouter(): Router`

Visszaadja a Router-t.

```php
$router = $app->getRouter();
```

#### `getLoader(): ModuleLoader`

Visszaadja a ModuleLoader-t.

```php
$loader = $app->getLoader();
```

#### `getBasePath(): string`

Visszaadja az alap könyvtárat.

```php
$basePath = $app->getBasePath();
```

#### `getConfig(): array`

Visszaadja a konfigurációt.

```php
$config = $app->getConfig();
```

## Példák

### Egyszerű API

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->run();
```

### Middleware-ekkel

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;
use MAAF\Core\Http\Middleware\LoggingMiddleware;
use MAAF\Core\Http\Middleware\CorsMiddleware;

$app = new Application(__DIR__ . '/..');
$app->addMiddleware(new LoggingMiddleware());
$app->addMiddleware(new CorsMiddleware(['*']));
$app->run();
```

### Custom Request Kezelés

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;
use MAAF\Core\Http\Request;

$app = new Application(__DIR__ . '/..');

// Custom request létrehozása
$request = Request::fromGlobals();
// ... request módosítás ...

$app->run($request);
```

### Response Módosítás

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;
use MAAF\Core\Http\Response;

$app = new Application(__DIR__ . '/..');

$response = $app->handle();

// Response módosítás
$response = $response->withHeader('X-Custom-Header', 'value');

$response->send();
```

## Konfigurációs Opciók

### Alapértelmezett Konfiguráció

```php
[
    'modules' => [
        'path' => $basePath . '/src/Modules',
        'namespace' => 'App\\Modules',
    ],
    'services' => $basePath . '/config/services.php',
    'routes' => $basePath . '/config/routes.php',
    'middleware' => [],
]
```

### Egyedi Konfiguráció

```php
$app = new Application(__DIR__ . '/..', [
    'modules' => [
        'path' => __DIR__ . '/../custom/Modules',
        'namespace' => 'MyApp\\Modules',
    ],
    'services' => __DIR__ . '/../custom/services.php',
    'routes' => __DIR__ . '/../custom/routes.php',
    'middleware' => [
        \MyApp\Middleware\CustomMiddleware::class,
    ],
]);
```

## Backward Compatibility

Az `Application` osztály opcionális. A jelenlegi módszer továbbra is működik:

```php
// Régi módszer (továbbra is működik)
$containerBuilder = new ContainerBuilder();
// ... stb.
```

## Best Practices

1. **Használd a konfigurációs fájlt** nagyobb projekteknél
2. **Minimális használat** kis projekteknél (konvenció alapú)
3. **Middleware-eket** a konfigurációban vagy `addMiddleware()`-vel add hozzá
4. **Teljes kontroll** csak akkor, ha szükséges

