# IDE Támogatás MAAF Core-hoz

A MAAF Core tartalmaz IDE támogatást a jobb autocomplete és type hints érdekében.

## PhpStorm Támogatás

### Automatikus Támogatás

A `.phpstorm.meta.php` fájl automatikusan betöltődik PhpStorm-ban, és biztosítja:

- ✅ DI Container autocomplete
- ✅ Application osztály metódusok autocomplete
- ✅ Request/Response metódusok autocomplete
- ✅ Router és ModuleLoader metódusok autocomplete
- ✅ Return type hints

### Kézi Beállítás (Ha szükséges)

1. **Settings** > **Languages & Frameworks** > **PHP**
2. **Include Path** > Add `maaf-core` könyvtár
3. **Meta Files** > Ellenőrizd, hogy `.phpstorm.meta.php` betöltődik

## VS Code Támogatás

### PHP Intelephense Extension

1. Telepítsd a **PHP Intelephense** extension-t
2. A `ide-helper.php` fájl automatikusan használható stubs-ként

### Beállítás

```json
{
    "php.suggest.basic": false,
    "intelephense.files.maxSize": 5000000,
    "intelephense.stubs": [
        "maaf-core/ide-helper.php"
    ]
}
```

## PHPStan Támogatás

### Telepítés

```bash
composer require --dev phpstan/phpstan
```

### Konfiguráció (`phpstan.neon`)

```yaml
parameters:
    level: 5
    paths:
        - src
    excludePaths:
        - vendor
```

### Futtatás

```bash
vendor/bin/phpstan analyse
```

## Autocomplete Példák

### Application Class

```php
use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
// IDE autocomplete: bootstrap(), run(), handle(), addMiddleware(), stb.
$app->bootstrap();
$app->addMiddleware(/* IDE suggests MiddlewareInterface types */);
```

### Request Class

```php
use MAAF\Core\Http\Request;

$request = Request::fromGlobals();
// IDE autocomplete: getPath(), getMethod(), getBody(), getQuery(), stb.
$path = $request->getPath();
$method = $request->getMethod(); // IDE knows: 'GET' | 'POST' | 'PUT' | ...
```

### Response Class

```php
use MAAF\Core\Http\Response;

// IDE autocomplete: json(), text(), html(), empty()
$response = Response::json(['status' => 'ok']);
$response->withHeader(/* IDE suggests string types */);
```

### DI Container

```php
$container = $app->getContainer();
// IDE autocomplete based on registered services
$service = $container->get(MyService::class);
```

## Támogatott IDE-k

- ✅ **PhpStorm** - Teljes támogatás (`.phpstorm.meta.php`)
- ✅ **VS Code** - PHP Intelephense extension-nel
- ✅ **NetBeans** - Alapvető támogatás
- ✅ **Eclipse PDT** - Alapvető támogatás

## További Fejlesztések

### Custom IDE Extension (Opcionális)

Ha szeretnél teljes IDE extension-t:

1. **VS Code Extension** - Language Server Protocol
2. **PhpStorm Plugin** - Custom inspections, code generation
3. **Snippets** - Code snippets a gyors fejlesztéshez

### Code Snippets

#### VS Code (`maaf-core/snippets/vscode.json`)

```json
{
    "MAAF Application": {
        "prefix": "maaf-app",
        "body": [
            "<?php",
            "require_once __DIR__ . '/../vendor/autoload.php';",
            "",
            "use MAAF\\Core\\Application;",
            "",
            "$app = new Application(__DIR__ . '/..');",
            "$app->run();"
        ]
    }
}
```

#### PhpStorm Live Templates

```
Abbreviation: maaf-app
Template text:
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MAAF\Core\Application;

$app = new Application(__DIR__ . '/..');
$app->run();
```

## Best Practices

1. **Használd a type hints-et** - Jobb IDE támogatás
2. **PHPStan futtatása** - Type safety ellenőrzés
3. **IDE helper fájlok** - Frissítsd, ha új metódusokat adsz hozzá
4. **Documentation comments** - PHPDoc kommentek az IDE-ben

## Hibaelhárítás

### PhpStorm nem ismeri fel a típusokat

1. **File** > **Invalidate Caches** > **Invalidate and Restart**
2. Ellenőrizd, hogy a `maaf-core` package telepítve van
3. **Settings** > **Languages & Frameworks** > **PHP** > **Include Path**

### VS Code nem ad autocomplete-ot

1. Telepítsd a **PHP Intelephense** extension-t
2. Ellenőrizd a `settings.json` fájlt
3. Restart VS Code

## További Információk

- [PhpStorm Meta Files](https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html)
- [PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client)
- [PHPStan](https://phpstan.org/)

