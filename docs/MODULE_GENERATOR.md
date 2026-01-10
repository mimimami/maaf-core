# Modul Generátor Dokumentáció

## Áttekintés

A MAAF Core modul generátor segít gyorsan létrehozni új modulokat különböző skeleton sablonokkal.

## Használat

### Alapvető Használat

```bash
php maaf make:module MyModule
```

Ez egy alapvető modult hoz létre a következő struktúrával:
```
src/Modules/MyModule/
├── Module.php
└── Controllers/
    └── MyModuleController.php
```

### Interaktív Mód

```bash
php maaf make:module MyModule --interactive
```

Az interaktív módban választható:
- Template típus (basic, api, crud, auth)
- Namespace
- Modul path
- Metaadatok (description, version, author)

### Template Választás

```bash
php maaf make:module MyModule --template=api
```

Elérhető template-ek:
- `basic` - Alap modul controller-rel
- `api` - RESTful API modul service-szel
- `crud` - CRUD modul repository pattern-nel
- `auth` - Authentication modul

### Opciók

```bash
php maaf make:module MyModule \
  --template=crud \
  --namespace="App\\Modules" \
  --path="src/Modules" \
  --description="My module description" \
  --version="1.0.0" \
  --author="John Doe"
```

## Template-ek

### Basic Template

Alap modul minimális funkcionalitással.

**Struktúra:**
- `Module.php` - Modul regisztráció
- `Controllers/MyModuleController.php` - Alap controller

### API Template

RESTful API modul teljes CRUD műveletekkel.

**Struktúra:**
- `Module.php` - Modul regisztráció route-okkal
- `Controllers/MyModuleController.php` - API controller
- `Services/MyModuleService.php` - Business logic

**Route-ok:**
- `GET /api/my-module` - List all
- `GET /api/my-module/{id}` - Get by ID
- `POST /api/my-module` - Create
- `PUT /api/my-module/{id}` - Update
- `DELETE /api/my-module/{id}` - Delete

### CRUD Template

CRUD modul repository pattern-nel és model-lel.

**Struktúra:**
- `Module.php` - Modul regisztráció
- `Controllers/MyModuleController.php` - Controller
- `Services/MyModuleService.php` - Service layer
- `Repositories/MyModuleRepository.php` - Repository layer
- `Models/MyModule.php` - Domain model

### Auth Template

Authentication modul login, register, logout funkcionalitással.

**Struktúra:**
- `Module.php` - Modul regisztráció
- `Controllers/AuthController.php` - Auth controller
- `Services/AuthService.php` - Auth service

**Route-ok:**
- `POST /auth/register` - Register
- `POST /auth/login` - Login
- `POST /auth/logout` - Logout
- `GET /auth/me` - Get current user

## Modul Validálás

A generált modulok validálhatók a következő paranccsal:

```bash
php maaf module:validate src/Modules/MyModule
```

A validátor ellenőrzi:
- Modul struktúrát
- Module.php fájl létezését és formátumát
- ModuleInterface implementációt
- Szükséges metódusok jelenlétét
- Metaadatokat

## Egyedi Template-ek

Saját template-eket is regisztrálhatsz:

```php
use MAAF\Core\ModuleGenerator\ModuleGenerator;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

class MyCustomTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'custom';
    }

    public function getDescription(): string
    {
        return 'My custom template';
    }

    public function getFiles(ModuleMetadata $metadata): array
    {
        // Return array of GeneratedFile objects
    }
}

$generator = new ModuleGenerator();
$generator->registerTemplate('custom', new MyCustomTemplate());
```

## Best Practices

1. **Modul nevek**: Használj PascalCase formátumot (pl. `UserManagement`)
2. **Namespace**: Következetes namespace struktúrát használj
3. **Template választás**: Válaszd ki a megfelelő template-et a feladathoz
4. **Validálás**: Mindig validáld a modult generálás után
5. **Dokumentáció**: Töltsd ki a metaadatokat (description, version, author)

## Példák

### API Modul Létrehozása

```bash
php maaf make:module ProductApi --template=api --interactive
```

### CRUD Modul Létrehozása

```bash
php maaf make:module UserManagement \
  --template=crud \
  --description="User management module" \
  --version="1.0.0"
```

### Modul Validálása

```bash
php maaf module:validate src/Modules/UserManagement
```
