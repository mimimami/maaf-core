# MAAF Module Template Konzisztencia Dokumentáció

## Áttekintés

Ez a dokumentum leírja a MAAF modul templatek konzisztens struktúráját és követelményeit.

## Template Lista

Minden template a következő struktúrát követi:

1. **BasicModuleTemplate** - Hexagonal Architecture alap modul
2. **CrudModuleTemplate** - CRUD műveletekhez
3. **ApiModuleTemplate** - RESTful API végpontokhoz (`/api/` prefix)
4. **AuthModuleTemplate** - Autentikációhoz
5. **UserModuleTemplate** - Felhasználó kezeléshez
6. **CourseModuleTemplate** - Kurzus kezeléshez
7. **InstitutionModuleTemplate** - Intézmény kezeléshez

## Mappa- és Fájlszerkezet

Minden template generálja a következő struktúrát:

```
ModuleName/
├── {ClassName}Module.php      # Fő modul osztály
├── composer.json              # Composer konfiguráció
├── routes.php                 # Route definíciók
├── Domain/
│   ├── Model/                 # Domain model osztályok
│   ├── Repository/            # Repository interface-ek
│   ├── Service/               # Service interface-ek
│   └── Exception/             # Domain exception-ök
├── Application/
│   ├── DTO/                   # Data Transfer Objects
│   ├── Validator/             # Validátor osztályok
│   └── Service/               # Application service implementációk
├── Infrastructure/
│   └── Repository/            # Repository implementációk (InMemory)
└── Presentation/
    └── Http/
        ├── Request/           # HTTP request wrapper-ek
        ├── Response/          # HTTP response formatter-ek
        └── Controller/        # HTTP controller-ek
```

## Module Osztály Struktúra

Minden generált modul osztály:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\{ModuleName};

use MAAF\Module\Module as BaseModule;
use DI\ContainerBuilder;
use MAAF\Core\Routing\Router;

final class {ClassName}Module extends BaseModule
{
    public function getName(): string
    {
        return '{moduleName}';
    }

    public function getVersion(): string
    {
        return '{version}';
    }

    public function getDescription(): string
    {
        return '{description}';
    }

    public function registerServices(ContainerBuilder $builder): void
    {
        // DI container regisztráció
    }

    public function registerRoutes(Router $router): void
    {
        $routes = require __DIR__ . '/routes.php';
        $routes($router);
    }

    public function boot(): void
    {
        // Called when module is loaded
    }

    public function activate(): void
    {
        // Called when module is activated
    }

    public function deactivate(): void
    {
        // Called when module is deactivated
    }
}
```

## Composer.json Struktúra

Minden template generál egy `composer.json` fájlt:

```json
{
    "name": "{namespace-lowercase}/{module-name-lowercase}",
    "description": "{description}",
    "type": "maaf-module",
    "keywords": ["maaf", "module", "{module-name}"],
    "license": "MIT",
    "authors": [
        {
            "name": "{author}",
            "email": ""
        }
    ],
    "require": {
        "php": ">=8.1",
        "maaf/core": "^2.0",
        "maaf/module": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "{Namespace}\\{ModuleName}\\": ""
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

## Namespace Konvenciók

- **Base namespace**: `$metadata->namespace` (pl. `Modules`, `App\Modules`)
- **Full namespace**: `{namespace}\{moduleName}` (pl. `Modules\User`, `App\Modules\Course`)
- **Domain namespace**: `{namespace}\{moduleName}\Domain\{Layer}`
- **Application namespace**: `{namespace}\{moduleName}\Application\{Layer}`
- **Infrastructure namespace**: `{namespace}\{moduleName}\Infrastructure\{Layer}`
- **Presentation namespace**: `{namespace}\{moduleName}\Presentation\Http\{Layer}`

## Routes.php Struktúra

Minden template generál egy `routes.php` fájlt:

```php
<?php

use MAAF\Core\Routing\Router;
use {Namespace}\{ModuleName}\Presentation\Http\Controller\{ClassName}Controller;

return function (Router $router): void {
    // Route definíciók
};
```

## Autoload Integráció

A generált modulok automatikusan betöltődnek, ha:
1. A `composer.json` tartalmazza a megfelelő PSR-4 autoload konfigurációt
2. A modul `composer.json`-ja be van építve a fő projekt `composer.json`-jába
3. A `Module.php` osztály implementálja a `MAAF\Module\ModuleInterface`-t

## CLI Parancs Integráció

A modulok CLI parancsokat regisztrálhatnak a következő módon:

1. **CLI/Commands/** mappában lévő parancsok automatikusan felderítődnek
2. A modul `getCommands()` metódusával lehet regisztrálni parancsokat
3. A parancsoknak implementálniuk kell a `MAAF\Core\Cli\CommandInterface`-t

Példa modul parancs struktúrája:

```
ModuleName/
└── CLI/
    └── Commands/
        └── {ModuleName}Command.php
```

## MAAF-kompatibilis Modulstruktúra

Minden generált modul:

1. **Extends**: `MAAF\Module\Module` base class
2. **Implements**: `MAAF\Module\ModuleInterface` (automatikusan a base class által)
3. **Lifecycle hooks**: `boot()`, `activate()`, `deactivate()`
4. **Service registration**: `registerServices(ContainerBuilder $builder)`
5. **Route registration**: `registerRoutes(Router $router)`

## Konzisztencia Ellenőrző Lista

- [x] Minden template Hexagonal Architecture struktúrát használ
- [x] Minden template generál `composer.json` fájlt
- [x] Minden template `MAAF\Module\Module` base class-t használ
- [x] Minden template konzisztens namespace-eket használ
- [x] Minden template `routes.php` fájlt generál
- [x] Minden template tartalmazza a lifecycle hook-okat
- [x] ModuleGenerator regisztrálja az összes template-et
- [x] MakeModuleCommand támogatja az összes template-et

## Használat

### CLI parancs:

```bash
php maaf make:module ModuleName --template=basic
php maaf make:module User --template=user
php maaf make:module Course --template=course --namespace=Modules
```

### Programozott használat:

```php
use MAAF\Core\ModuleGenerator\ModuleGenerator;
use MAAF\Core\ModuleGenerator\ModuleMetadata;

$generator = new ModuleGenerator();
$metadata = new ModuleMetadata(
    name: 'User',
    namespace: 'Modules',
    description: 'User management module',
    version: '1.0.0',
    author: 'Your Name',
    template: 'user'
);

$result = $generator->generate('User', 'user', 'modules', $metadata);
```
