<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * User Module Template
 * 
 * Felhasználó modul sablon Hexagonal / Clean Architecture struktúrával.
 * 
 * @version 1.0.0
 */
final class UserModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'user';
    }

    public function getDescription(): string
    {
        return 'User module with authentication and role management (Hexagonal Architecture)';
    }

    /**
     * @return GeneratedFile[]
     */
    public function getFiles(ModuleMetadata $metadata): array
    {
        $moduleName = $metadata->name;
        $className  = 'User';
        $namespace  = $metadata->namespace . '\\' . $moduleName;

        return [
            // Gyökér szint
            new GeneratedFile(
                'UserModule.php',
                $this->getModuleClassContent($namespace, $className, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'composer.json',
                $this->getComposerJsonContent($namespace, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'routes.php',
                $this->getRoutesContent($namespace),
            ),

            // Domain réteg
            new GeneratedFile(
                'Domain/Model/User.php',
                $this->getDomainModelContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Repository/UserRepositoryInterface.php',
                $this->getDomainRepositoryInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Service/UserServiceInterface.php',
                $this->getDomainServiceInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Exception/UserNotFoundException.php',
                $this->getDomainNotFoundExceptionContent($namespace, $className),
            ),

            // Application réteg
            new GeneratedFile(
                'Application/DTO/UserRequestDTO.php',
                $this->getApplicationRequestDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/DTO/UserResponseDTO.php',
                $this->getApplicationResponseDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Validator/UserRequestValidator.php',
                $this->getApplicationRequestValidatorContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Service/UserService.php',
                $this->getApplicationServiceContent($namespace, $className),
            ),

            // Infrastructure réteg
            new GeneratedFile(
                'Infrastructure/Repository/InMemoryUserRepository.php',
                $this->getInfrastructureRepositoryContent($namespace, $className),
            ),

            // Presentation réteg (HTTP)
            new GeneratedFile(
                'Presentation/Http/Request/UserRequest.php',
                $this->getPresentationRequestContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Response/UserResponse.php',
                $this->getPresentationResponseContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Controller/UserController.php',
                $this->getPresentationControllerContent($namespace, $className),
            ),
        ];
    }

    private function getModuleClassContent(string $namespace, string $className, string $moduleName, ModuleMetadata $metadata): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use MAAF\\Module\\Module as BaseModule;
use DI\\ContainerBuilder;
use MAAF\\Core\\Routing\\Router;

final class UserModule extends BaseModule
{
    public function getName(): string
    {
        return '{$moduleName}';
    }

    public function getVersion(): string
    {
        return '{$metadata->version}';
    }

    public function getDescription(): string
    {
        return '{$metadata->description}';
    }

    public function registerServices(ContainerBuilder \$builder): void
    {
        \$builder->addDefinitions([
            Domain\\Repository\\UserRepositoryInterface::class => DI\create(Infrastructure\\Repository\\InMemoryUserRepository::class),
            Domain\\Service\\UserServiceInterface::class => DI\create(Application\\Service\\UserService::class)
                ->constructor(
                    DI\get(Domain\\Repository\\UserRepositoryInterface::class),
                    DI\create(Application\\Validator\\UserRequestValidator::class)
                ),
        ]);
    }

    public function registerRoutes(Router \$router): void
    {
        \$routes = require __DIR__ . '/routes.php';
        \$routes(\$router);
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

PHP;
    }

    private function getComposerJsonContent(string $namespace, string $moduleName, ModuleMetadata $metadata): string
    {
        $packageName = strtolower(str_replace('\\', '/', $namespace));
        
        return <<<JSON
{
    "name": "{$packageName}",
    "description": "{$metadata->description}",
    "type": "maaf-module",
    "keywords": ["maaf", "module", "{$moduleName}"],
    "license": "MIT",
    "authors": [
        {
            "name": "{$metadata->author}",
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
            "{$namespace}\\\\": ""
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}

JSON;
    }

    private function getRoutesContent(string $namespace): string
    {
        $base = 'users';

        return <<<PHP
<?php

use MAAF\\Core\\Routing\\Router;
use {$namespace}\\Presentation\\Http\\Controller\\UserController;

return function (Router \$router): void {
    \$router->get('/{$base}', [UserController::class, 'index']);
    \$router->get('/{$base}/{id}', [UserController::class, 'show']);
    \$router->post('/{$base}', [UserController::class, 'create']);
    \$router->put('/{$base}/{id}', [UserController::class, 'update']);
    \$router->delete('/{$base}/{id}', [UserController::class, 'delete']);
    \$router->post('/{$base}/{id}/activate', [UserController::class, 'activate']);
    \$router->post('/{$base}/{id}/deactivate', [UserController::class, 'deactivate']);
};

PHP;
    }

    private function getDomainModelContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Model;

final class User
{
    public function __construct(
        private string \$id,
        private string \$email,
        private string \$name,
        private string \$password,
        private string \$role = 'user',
        private bool \$isActive = true,
        private ?string \$avatar = null,
        private ?string \$phone = null,
        private ?\DateTimeImmutable \$lastLoginAt = null,
    ) {}

    public function getId(): string
    {
        return \$this->id;
    }

    public function getEmail(): string
    {
        return \$this->email;
    }

    public function getName(): string
    {
        return \$this->name;
    }

    public function getPassword(): string
    {
        return \$this->password;
    }

    public function getRole(): string
    {
        return \$this->role;
    }

    public function isActive(): bool
    {
        return \$this->isActive;
    }

    public function getAvatar(): ?string
    {
        return \$this->avatar;
    }

    public function getPhone(): ?string
    {
        return \$this->phone;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return \$this->lastLoginAt;
    }

    public function verifyPassword(string \$password): bool
    {
        return password_verify(\$password, \$this->password);
    }

    public function activate(): void
    {
        \$this->isActive = true;
    }

    public function deactivate(): void
    {
        \$this->isActive = false;
    }

    public function updateEmail(string \$email): void
    {
        \$this->email = \$email;
    }

    public function updateName(string \$name): void
    {
        \$this->name = \$name;
    }

    public function updatePassword(string \$password): void
    {
        \$this->password = password_hash(\$password, PASSWORD_DEFAULT);
    }

    public function updateRole(string \$role): void
    {
        \$this->role = \$role;
    }

    public function recordLogin(): void
    {
        \$this->lastLoginAt = new \DateTimeImmutable();
    }
}

PHP;
    }

    private function getDomainRepositoryInterfaceContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Repository;

use {$namespace}\\Domain\\Model\\User;

interface UserRepositoryInterface
{
    /**
     * @return User[]
     */
    public function findAll(): array;

    public function findById(string \$id): ?User;

    public function findByEmail(string \$email): ?User;

    public function save(User \$user): User;

    public function delete(string \$id): bool;
}

PHP;
    }

    private function getDomainServiceInterfaceContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Service;

use {$namespace}\\Domain\\Model\\User;

interface UserServiceInterface
{
    /**
     * @return User[]
     */
    public function list(): array;

    public function get(string \$id): User;

    public function getByEmail(string \$email): ?User;

    public function create(string \$email, string \$name, string \$password, string \$role = 'user'): User;

    public function update(string \$id, array \$data): User;

    public function delete(string \$id): void;

    public function activate(string \$id): User;

    public function deactivate(string \$id): User;
}

PHP;
    }

    private function getDomainNotFoundExceptionContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Exception;

final class UserNotFoundException extends \\RuntimeException
{
}

PHP;
    }

    private function getApplicationRequestDtoContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\DTO;

final class UserRequestDTO
{
    public function __construct(
        private ?string \$id = null,
        private ?string \$email = null,
        private ?string \$name = null,
        private ?string \$password = null,
        private ?string \$role = null,
        private ?bool \$isActive = null,
        private ?string \$avatar = null,
        private ?string \$phone = null,
    ) {}

    public function getId(): ?string
    {
        return \$this->id;
    }

    public function getEmail(): ?string
    {
        return \$this->email;
    }

    public function getName(): ?string
    {
        return \$this->name;
    }

    public function getPassword(): ?string
    {
        return \$this->password;
    }

    public function getRole(): ?string
    {
        return \$this->role;
    }

    public function getIsActive(): ?bool
    {
        return \$this->isActive;
    }

    public function getAvatar(): ?string
    {
        return \$this->avatar;
    }

    public function getPhone(): ?string
    {
        return \$this->phone;
    }
}

PHP;
    }

    private function getApplicationResponseDtoContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\DTO;

final class UserResponseDTO
{
    public function __construct(
        private string \$id,
        private string \$email,
        private string \$name,
        private string \$role,
        private bool \$isActive,
        private ?string \$avatar = null,
        private ?string \$phone = null,
        private ?string \$lastLoginAt = null,
    ) {}

    public function getId(): string
    {
        return \$this->id;
    }

    public function getEmail(): string
    {
        return \$this->email;
    }

    public function getName(): string
    {
        return \$this->name;
    }

    public function getRole(): string
    {
        return \$this->role;
    }

    public function isActive(): bool
    {
        return \$this->isActive;
    }

    public function getAvatar(): ?string
    {
        return \$this->avatar;
    }

    public function getPhone(): ?string
    {
        return \$this->phone;
    }

    public function getLastLoginAt(): ?string
    {
        return \$this->lastLoginAt;
    }

    public function toArray(): array
    {
        return [
            'id' => \$this->id,
            'email' => \$this->email,
            'name' => \$this->name,
            'role' => \$this->role,
            'is_active' => \$this->isActive,
            'avatar' => \$this->avatar,
            'phone' => \$this->phone,
            'last_login_at' => \$this->lastLoginAt,
        ];
    }
}

PHP;
    }

    private function getApplicationRequestValidatorContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\Validator;

use {$namespace}\\Application\\DTO\\UserRequestDTO;

final class UserRequestValidator
{
    /**
     * @return string[] Hibák listája
     */
    public function validateForList(): array
    {
        return [];
    }

    /**
     * @return string[] Hibák listája
     */
    public function validateForGet(UserRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getId() === null || \$dto->getId() === '') {
            \$errors[] = 'ID is required';
        }

        return \$errors;
    }

    /**
     * @return string[] Hibák listája
     */
    public function validateForCreate(UserRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getEmail() === null || \$dto->getEmail() === '') {
            \$errors[] = 'Email is required';
        } elseif (!filter_var(\$dto->getEmail(), FILTER_VALIDATE_EMAIL)) {
            \$errors[] = 'Email is invalid';
        }

        if (\$dto->getName() === null || \$dto->getName() === '') {
            \$errors[] = 'Name is required';
        }

        if (\$dto->getPassword() === null || \$dto->getPassword() === '') {
            \$errors[] = 'Password is required';
        } elseif (strlen(\$dto->getPassword()) < 8) {
            \$errors[] = 'Password must be at least 8 characters';
        }

        return \$errors;
    }

    /**
     * @return string[] Hibák listája
     */
    public function validateForUpdate(UserRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getId() === null || \$dto->getId() === '') {
            \$errors[] = 'ID is required';
        }

        if (\$dto->getEmail() !== null && !filter_var(\$dto->getEmail(), FILTER_VALIDATE_EMAIL)) {
            \$errors[] = 'Email is invalid';
        }

        if (\$dto->getPassword() !== null && strlen(\$dto->getPassword()) < 8) {
            \$errors[] = 'Password must be at least 8 characters';
        }

        return \$errors;
    }
}

PHP;
    }

    private function getApplicationServiceContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\Service;

use {$namespace}\\Application\\DTO\\UserRequestDTO;
use {$namespace}\\Application\\DTO\\UserResponseDTO;
use {$namespace}\\Application\\Validator\\UserRequestValidator;
use {$namespace}\\Domain\\Exception\\UserNotFoundException;
use {$namespace}\\Domain\\Repository\\UserRepositoryInterface;
use {$namespace}\\Domain\\Service\\UserServiceInterface;

final class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface \$repository,
        private UserRequestValidator \$validator,
    ) {}

    public function list(): array
    {
        \$items = \$this->repository->findAll();

        return array_map(
            fn(\$item) => new UserResponseDTO(
                \$item->getId(),
                \$item->getEmail(),
                \$item->getName(),
                \$item->getRole(),
                \$item->isActive(),
                \$item->getAvatar(),
                \$item->getPhone(),
                \$item->getLastLoginAt()?->format('Y-m-d H:i:s')
            ),
            \$items
        );
    }

    public function get(string \$id)
    {
        \$dto = new UserRequestDTO(id: \$id);

        \$errors = \$this->validator->validateForGet(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$item = \$this->repository->findById(\$id);
        if (!\$item) {
            throw new UserNotFoundException("User not found: {\$id}");
        }

        return new UserResponseDTO(
            \$item->getId(),
            \$item->getEmail(),
            \$item->getName(),
            \$item->getRole(),
            \$item->isActive(),
            \$item->getAvatar(),
            \$item->getPhone(),
            \$item->getLastLoginAt()?->format('Y-m-d H:i:s')
        );
    }

    public function getByEmail(string \$email): ?UserResponseDTO
    {
        \$item = \$this->repository->findByEmail(\$email);
        if (!\$item) {
            return null;
        }

        return new UserResponseDTO(
            \$item->getId(),
            \$item->getEmail(),
            \$item->getName(),
            \$item->getRole(),
            \$item->isActive(),
            \$item->getAvatar(),
            \$item->getPhone(),
            \$item->getLastLoginAt()?->format('Y-m-d H:i:s')
        );
    }

    public function create(string \$email, string \$name, string \$password, string \$role = 'user'): \{$namespace}\\Domain\\Model\\User
    {
        \$dto = new UserRequestDTO(
            email: \$email,
            name: \$name,
            password: \$password,
            role: \$role
        );

        \$errors = \$this->validator->validateForCreate(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$user = new \{$namespace}\\Domain\\Model\\User(
            id: uniqid('user_', true),
            email: \$email,
            name: \$name,
            password: password_hash(\$password, PASSWORD_DEFAULT),
            role: \$role
        );

        return \$this->repository->save(\$user);
    }

    public function update(string \$id, array \$data): \{$namespace}\\Domain\\Model\\User
    {
        \$dto = new UserRequestDTO(
            id: \$id,
            email: \$data['email'] ?? null,
            name: \$data['name'] ?? null,
            password: \$data['password'] ?? null,
            role: \$data['role'] ?? null,
            isActive: \$data['is_active'] ?? null,
            avatar: \$data['avatar'] ?? null,
            phone: \$data['phone'] ?? null,
        );

        \$errors = \$this->validator->validateForUpdate(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$user = \$this->repository->findById(\$id);
        if (!\$user) {
            throw new UserNotFoundException("User not found: {\$id}");
        }

        if (\$dto->getEmail() !== null) {
            \$user->updateEmail(\$dto->getEmail());
        }
        if (\$dto->getName() !== null) {
            \$user->updateName(\$dto->getName());
        }
        if (\$dto->getPassword() !== null) {
            \$user->updatePassword(\$dto->getPassword());
        }
        if (\$dto->getRole() !== null) {
            \$user->updateRole(\$dto->getRole());
        }
        if (\$dto->getIsActive() !== null) {
            if (\$dto->getIsActive()) {
                \$user->activate();
            } else {
                \$user->deactivate();
            }
        }

        return \$this->repository->save(\$user);
    }

    public function delete(string \$id): void
    {
        \$user = \$this->repository->findById(\$id);
        if (!\$user) {
            throw new UserNotFoundException("User not found: {\$id}");
        }

        \$this->repository->delete(\$id);
    }

    public function activate(string \$id): \{$namespace}\\Domain\\Model\\User
    {
        \$user = \$this->repository->findById(\$id);
        if (!\$user) {
            throw new UserNotFoundException("User not found: {\$id}");
        }

        \$user->activate();
        return \$this->repository->save(\$user);
    }

    public function deactivate(string \$id): \{$namespace}\\Domain\\Model\\User
    {
        \$user = \$this->repository->findById(\$id);
        if (!\$user) {
            throw new UserNotFoundException("User not found: {\$id}");
        }

        \$user->deactivate();
        return \$this->repository->save(\$user);
    }
}

PHP;
    }

    private function getInfrastructureRepositoryContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Infrastructure\\Repository;

use {$namespace}\\Domain\\Model\\User;
use {$namespace}\\Domain\\Repository\\UserRepositoryInterface;

/**
 * In-memory repository példa.
 * Később cserélhető adatbázisos implementációra.
 */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array \$items = [];

    public function __construct()
    {
        \$this->items = [
            'user_1' => new User('user_1', 'admin@example.com', 'Admin User', password_hash('password', PASSWORD_DEFAULT), 'admin', true),
            'user_2' => new User('user_2', 'user@example.com', 'Regular User', password_hash('password', PASSWORD_DEFAULT), 'user', true),
        ];
    }

    public function findAll(): array
    {
        return array_values(\$this->items);
    }

    public function findById(string \$id): ?User
    {
        return \$this->items[\$id] ?? null;
    }

    public function findByEmail(string \$email): ?User
    {
        foreach (\$this->items as \$user) {
            if (\$user->getEmail() === \$email) {
                return \$user;
            }
        }
        return null;
    }

    public function save(User \$user): User
    {
        \$this->items[\$user->getId()] = \$user;
        return \$user;
    }

    public function delete(string \$id): bool
    {
        if (isset(\$this->items[\$id])) {
            unset(\$this->items[\$id]);
            return true;
        }
        return false;
    }
}

PHP;
    }

    private function getPresentationRequestContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Presentation\\Http\\Request;

/**
 * HTTP request wrapper példa.
 * Később integrálható konkrét HTTP request objektummal.
 */
final class UserRequest
{
    public function __construct(
        private array \$queryParams = [],
        private array \$bodyParams = [],
    ) {}

    public function getId(): ?string
    {
        return \$this->queryParams['id'] ?? null;
    }

    public function getEmail(): ?string
    {
        return \$this->bodyParams['email'] ?? null;
    }

    public function getName(): ?string
    {
        return \$this->bodyParams['name'] ?? null;
    }

    public function getPassword(): ?string
    {
        return \$this->bodyParams['password'] ?? null;
    }

    public function getRole(): ?string
    {
        return \$this->bodyParams['role'] ?? null;
    }

    public function getAll(): array
    {
        return [
            'query' => \$this->queryParams,
            'body'  => \$this->bodyParams,
        ];
    }
}

PHP;
    }

    private function getPresentationResponseContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Presentation\\Http\\Response;

use {$namespace}\\Application\\DTO\\UserResponseDTO;

final class UserResponse
{
    /**
     * @param UserResponseDTO[] \$items
     */
    public static function list(array \$items): array
    {
        return array_map(
            fn(UserResponseDTO \$dto) => \$dto->toArray(),
            \$items
        );
    }

    public static function single(UserResponseDTO \$dto): array
    {
        return \$dto->toArray();
    }
}

PHP;
    }

    private function getPresentationControllerContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Presentation\\Http\\Controller;

use {$namespace}\\Application\\Service\\UserService;
use {$namespace}\\Presentation\\Http\\Request\\UserRequest;
use {$namespace}\\Presentation\\Http\\Response\\UserResponse;
use MAAF\\Core\\Http\\Response;

final class UserController
{
    public function __construct(
        private UserService \$service,
    ) {}

    public function index(): array
    {
        \$items = \$this->service->list();

        return UserResponse::list(\$items);
    }

    public function show(string \$id): array
    {
        \$item = \$this->service->get(\$id);

        return UserResponse::single(\$item);
    }

    public function create(UserRequest \$request): array
    {
        \$user = \$this->service->create(
            \$request->getEmail() ?? '',
            \$request->getName() ?? '',
            \$request->getPassword() ?? '',
            \$request->getRole() ?? 'user'
        );

        return [
            'id' => \$user->getId(),
            'email' => \$user->getEmail(),
            'name' => \$user->getName(),
            'role' => \$user->getRole(),
        ];
    }

    public function update(string \$id, UserRequest \$request): array
    {
        \$data = [];
        if (\$request->getEmail() !== null) {
            \$data['email'] = \$request->getEmail();
        }
        if (\$request->getName() !== null) {
            \$data['name'] = \$request->getName();
        }
        if (\$request->getPassword() !== null) {
            \$data['password'] = \$request->getPassword();
        }
        if (\$request->getRole() !== null) {
            \$data['role'] = \$request->getRole();
        }

        \$user = \$this->service->update(\$id, \$data);

        return [
            'id' => \$user->getId(),
            'email' => \$user->getEmail(),
            'name' => \$user->getName(),
            'role' => \$user->getRole(),
        ];
    }

    public function delete(string \$id): Response
    {
        \$this->service->delete(\$id);
        return Response::json(['message' => 'User deleted'], 204);
    }

    public function activate(string \$id): array
    {
        \$user = \$this->service->activate(\$id);

        return [
            'id' => \$user->getId(),
            'email' => \$user->getEmail(),
            'is_active' => \$user->isActive(),
        ];
    }

    public function deactivate(string \$id): array
    {
        \$user = \$this->service->deactivate(\$id);

        return [
            'id' => \$user->getId(),
            'email' => \$user->getEmail(),
            'is_active' => \$user->isActive(),
        ];
    }
}

PHP;
    }
}
