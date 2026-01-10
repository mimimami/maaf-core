# MAAF Core Dokumentáció

## Áttekintés

MAAF Core 1.0 stabil kiadás tartalmazza az összes alapvető komponenst a framework működéséhez.

## Komponensek

### 1. DI Container 1.0

Dependency Injection Container stabil API PHP-DI alapján.

**Használat:**

```php
use MAAF\Core\Container\Container;

// Üres container létrehozása
$container = new Container();

// Container létrehozása definíciókkal
$container = Container::fromDefinitions([
    'db.host' => 'localhost',
    'db.name' => 'myapp',
]);

// Entry lekérése
$dbHost = $container->get('db.host');

// Entry beállítása
$container->set('my.service', new MyService());

// Osztály példányosítása dependency injection-nel
$instance = $container->make(MyClass::class);
```

### 2. Module Loader 3.0

Automatikus modul betöltő rendszer.

**Modul létrehozása:**

```php
namespace App\Modules\MyModule;

use DI\ContainerBuilder;
use MAAF\Core\ModuleLoader\ModuleInterface;
use MAAF\Core\Routing\Router;

final class Module implements ModuleInterface
{
    public static function registerServices(ContainerBuilder $builder): void
    {
        // Service regisztráció
        $builder->addDefinitions([
            MyService::class => DI\create(MyService::class),
        ]);
    }

    public static function registerRoutes(Router $router): void
    {
        // Route regisztráció
        $router->get('/my-route', [MyController::class, 'index']);
    }
}
```

**Modul betöltése:**

```php
use MAAF\Core\ModuleLoader\ModuleLoader;

$moduleLoader = new ModuleLoader($container, $router);
$moduleLoader->loadModules(
    __DIR__ . '/src/Modules',
    'App\\Modules'
);
```

### 3. EventBus 1.0

Eseménykezelő rendszer.

**Használat:**

```php
use MAAF\Core\EventBus\EventBus;

$eventBus = new EventBus();

// Event listener regisztrálása
$eventBus->subscribe('user.created', function ($payload) {
    echo "User created: " . $payload['name'];
}, 10); // Priority: 10

// Event publikálása
$eventBus->publish('user.created', ['name' => 'John Doe']);

// Listener leiratkozása
$eventBus->unsubscribe('user.created', $listener);
```

### 4. Config Engine 1.0

Konfigurációs motor dot notation támogatással.

**Használat:**

```php
use MAAF\Core\Config\Config;

$config = new Config();

// Konfiguráció betöltése fájlból
$config->loadFromFile(__DIR__ . '/config/app.php');

// Konfiguráció betöltése tömbből
$config->loadFromArray([
    'database' => [
        'host' => 'localhost',
        'name' => 'myapp',
    ],
]);

// Érték lekérése (dot notation)
$dbHost = $config->get('database.host');
$dbName = $config->get('database.name', 'default'); // default értékkel

// Érték beállítása
$config->set('database.port', 3306);

// Ellenőrzés
if ($config->has('database.host')) {
    // ...
}
```

### 5. HTTP Kernel 1.0

HTTP kernel request-response kezeléssel.

**Request használat:**

```php
use MAAF\Core\Http\Request;

$request = Request::fromGlobals();

// HTTP metódus
$method = $request->getMethod(); // GET, POST, etc.

// URI és path
$uri = $request->getUri();
$path = $request->getPath();

// Headers
$contentType = $request->getHeader('Content-Type');

// Query parameters
$id = $request->getQuery('id');

// POST parameters
$name = $request->getPost('name');

// Request body (JSON)
$body = $request->getParsedBody();
```

**Response használat:**

```php
use MAAF\Core\Http\Response;

// JSON response
$response = Response::json(['message' => 'Success']);

// HTML response
$response = Response::html('<h1>Hello</h1>');

// Text response
$response = Response::text('Plain text');

// Redirect
$response = Response::redirect('/login');

// Custom response
$response = new Response(200, ['X-Custom' => 'value'], 'Body');
$response = $response->withHeader('X-Another', 'value');
$response->send();
```

**Middleware:**

```php
use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

final class MyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Before
        $response = $next($request);
        // After
        return $response->withHeader('X-Custom', 'value');
    }
}
```

**Routing:**

```php
use MAAF\Core\Routing\Router;

$router = new Router();

// Route hozzáadása
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'create']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'delete']);

// Multiple methods
$router->addRoute(['GET', 'POST'], '/api', $handler);
```

**Application:**

```php
use MAAF\Core\Application;

$app = new Application(__DIR__);

// Middleware hozzáadása
$app->addMiddleware(new CorsMiddleware());
$app->addMiddleware(new AuthMiddleware());

// Application futtatása
$app->run();
```

### 6. CLI 1.0

Command Line Interface rendszer.

**Parancs létrehozása:**

```php
use MAAF\Core\Cli\CommandInterface;

final class MyCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'my:command';
    }

    public function getDescription(): string
    {
        return 'My custom command';
    }

    public function execute(array $args): int
    {
        echo "Executing my command\n";
        return 0; // Success
    }
}
```

**CLI használat:**

```php
use MAAF\Core\Cli\Cli;

$cli = new Cli($container);

// Custom parancs regisztrálása
$cli->register(new MyCommand());

// CLI futtatása
$exitCode = $cli->run($argv);
```

## Telepítés

```bash
composer require maaf/core
```

## További információk

- [API Dokumentáció](api.md)
- [Példák](examples.md)
- [Best Practices](best-practices.md)
