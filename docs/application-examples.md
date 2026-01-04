# Application Class - Példák

## Alapvető Példák

### 1. Minimális Használat

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->run();
```

### 2. Middleware-ekkel

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

### 3. Konfigurációval

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..', [
    'modules' => [
        'path' => __DIR__ . '/../src/Modules',
        'namespace' => 'App\\Modules',
    ],
    'middleware' => [
        \MAAF\Core\Http\Middleware\LoggingMiddleware::class,
    ],
]);

$app->run();
```

### 4. Konfigurációs Fájllal

**config/maaf.php:**
```php
<?php

return [
    'modules' => [
        'path' => __DIR__ . '/../src/Modules',
        'namespace' => 'App\\Modules',
    ],
    'services' => __DIR__ . '/../config/services.php',
    'routes' => __DIR__ . '/../config/routes.php',
    'middleware' => [
        \MAAF\Core\Http\Middleware\LoggingMiddleware::class,
        \MAAF\Core\Http\Middleware\CorsMiddleware::class,
    ],
];
```

**public/index.php:**
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
// Automatikusan betölti a config/maaf.php fájlt
$app->run();
```

## Haladó Példák

### 5. Custom Request Kezelés

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;
use MAAF\Core\Http\Request;

$app = new Application(__DIR__ . '/..');
$app->bootstrap();

// Custom request létrehozása
$request = Request::fromGlobals();
// ... request módosítás ...

$app->run($request);
```

### 6. Response Módosítás

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

### 7. Container Használat

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->bootstrap();

$container = $app->getContainer();
$service = $container->get(MyService::class);

// ... service használat ...

$app->run();
```

### 8. Router Használat

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->bootstrap();

$router = $app->getRouter();
$routes = $router->getRoutes();

// Route információk
foreach ($routes as $route) {
    [$method, $path, $handler] = $route;
    echo "{$method} {$path}\n";
}

$app->run();
```

### 9. Dinamikus Middleware Hozzáadás

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');

// Feltételes middleware hozzáadás
if (getenv('APP_ENV') !== 'production') {
    $app->addMiddleware(new \MAAF\Core\Http\Middleware\LoggingMiddleware());
}

$app->addMiddleware(new \MAAF\Core\Http\Middleware\CorsMiddleware(['*']));

$app->run();
```

### 10. Teljes Kontroll

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

$app = new Application(__DIR__ . '/..');

// Bootstrap
$app->bootstrap();

// Container elérése
$container = $app->getContainer();

// Kernel elérése
$kernel = $app->getKernel();

// Custom middleware
$app->addMiddleware(function (Request $request, callable $next): Response {
    // Custom logic
    return $next($request);
});

// Request kezelés
$app->run();
```

## Valós Világ Példák

### API Projekt

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;
use MAAF\Core\Http\Middleware\CorsMiddleware;
use MAAF\Core\Http\Middleware\RateLimitingMiddleware;

$app = new Application(__DIR__ . '/..', [
    'middleware' => [
        CorsMiddleware::class,
    ],
]);

$app->addMiddleware(new RateLimitingMiddleware(100, 60));
$app->run();
```

### Web Alkalmazás

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;
use MAAF\Core\Http\Middleware\LoggingMiddleware;

$app = new Application(__DIR__ . '/..');
$app->addMiddleware(new LoggingMiddleware());
$app->run();
```

### Tesztelés

```php
<?php

use MAAF\Core\Application;
use MAAF\Core\Http\Request;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    public function testApplicationBootstrap(): void
    {
        $app = new Application(__DIR__ . '/..');
        $app->bootstrap();
        
        $this->assertNotNull($app->getContainer());
        $this->assertNotNull($app->getKernel());
    }
    
    public function testApplicationHandle(): void
    {
        $app = new Application(__DIR__ . '/..');
        
        $request = new Request();
        // ... request setup ...
        
        $response = $app->handle($request);
        
        $this->assertInstanceOf(\MAAF\Core\Http\Response::class, $response);
    }
}
```

