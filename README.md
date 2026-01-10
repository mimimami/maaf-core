# MAAF Core 1.0

MAAF Core Framework - Stabil kiadás

## Komponensek

- ✅ **DI Container 1.0** - Stabil Dependency Injection API
- ✅ **Module Loader 3.0** - Modul betöltő rendszer
- ✅ **EventBus 1.0** - Eseménykezelő rendszer
- ✅ **Async EventBus 2.0** - Aszinkron EventBus RabbitMQ/Redis Streams támogatással
- ✅ **Config Engine 1.0** - Konfigurációs motor
- ✅ **HTTP Kernel 1.0** - HTTP kernel
- ✅ **CLI 1.0** - Command Line Interface
- ✅ **Module Generator** - Modul generátor skeleton sablonokkal
- ✅ **Testing Toolkit 1.0** - Tesztelési segédeszközök

## Telepítés

```bash
composer require maaf/core
```

## Gyors Kezdés

### Application Bootstrap

```php
use MAAF\Core\Application;

$app = new Application(__DIR__);
$app->run();
```

### Modul Létrehozása

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
    }

    public static function registerRoutes(Router $router): void
    {
        $router->get('/my-route', [MyController::class, 'index']);
    }
}
```

### Controller

```php
namespace App\Modules\MyModule\Controllers;

use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

final class MyController
{
    public function index(Request $request): Response
    {
        return Response::json(['message' => 'Hello MAAF!']);
    }
}
```

### Tesztelés

```php
use MAAF\Core\Testing\TestCase;

class MyModuleTest extends TestCase
{
    public function testModuleLoads(): void
    {
        $this->moduleHelper->loadModule(MyModule::class, 'MyModule');
        $this->moduleHelper->assertModuleLoaded('MyModule');
    }
}
```

## Dokumentáció

- [Általános Dokumentáció](docs/README.md)
- [Module Generator](docs/MODULE_GENERATOR.md)
- [Async EventBus](docs/ASYNC_EVENTBUS.md)
- [Testing Toolkit](docs/TESTING_TOOLKIT.md)

## Verzió

**2.1.0** - Testing Toolkit hozzáadva

## Licenc

MIT License
