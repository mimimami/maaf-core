<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * Auth Module Template
 * 
 * Authentication modul sablon Hexagonal / Clean Architecture struktúrával.
 * 
 * @version 1.0.0
 */
final class AuthModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'auth';
    }

    public function getDescription(): string
    {
        return 'Authentication module with login, register, logout (Hexagonal Architecture)';
    }

    /**
     * @return GeneratedFile[]
     */
    public function getFiles(ModuleMetadata $metadata): array
    {
        $moduleName = $metadata->name;
        $className  = 'Auth';
        $namespace  = $metadata->namespace . '\\' . $moduleName;

        return [
            // Gyökér szint
            new GeneratedFile(
                $className . 'Module.php',
                $this->getModuleClassContent($namespace, $className, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'composer.json',
                $this->getComposerJsonContent($namespace, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'routes.php',
                $this->getRoutesContent($namespace, $className),
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
                'Domain/Service/AuthServiceInterface.php',
                $this->getDomainServiceInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Exception/InvalidCredentialsException.php',
                $this->getDomainInvalidCredentialsExceptionContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Exception/UserNotFoundException.php',
                $this->getDomainUserNotFoundExceptionContent($namespace, $className),
            ),

            // Application réteg
            new GeneratedFile(
                'Application/DTO/RegisterRequestDTO.php',
                $this->getApplicationRegisterRequestDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/DTO/LoginRequestDTO.php',
                $this->getApplicationLoginRequestDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/DTO/AuthResponseDTO.php',
                $this->getApplicationAuthResponseDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Validator/AuthRequestValidator.php',
                $this->getApplicationRequestValidatorContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Service/AuthService.php',
                $this->getApplicationServiceContent($namespace, $className),
            ),

            // Infrastructure réteg
            new GeneratedFile(
                'Infrastructure/Repository/InMemoryUserRepository.php',
                $this->getInfrastructureRepositoryContent($namespace, $className),
            ),

            // Presentation réteg (HTTP)
            new GeneratedFile(
                'Presentation/Http/Request/AuthRequest.php',
                $this->getPresentationRequestContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Response/AuthResponse.php',
                $this->getPresentationResponseContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Controller/AuthController.php',
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

final class {$className}Module extends BaseModule
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
            Domain\\Service\\AuthServiceInterface::class => DI\create(Application\\Service\\AuthService::class)
                ->constructor(
                    DI\get(Domain\\Repository\\UserRepositoryInterface::class),
                    DI\create(Application\\Validator\\AuthRequestValidator::class)
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
        \$packageName = strtolower(str_replace('\\\\', '/', \$namespace));
        
        return <<<JSON
{
    "name": "{\$packageName}",
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

    private function getRoutesContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

use MAAF\\Core\\Routing\\Router;
use {$namespace}\\Presentation\\Http\\Controller\\AuthController;

return function (Router \$router): void {
    \$router->post('/auth/register', [AuthController::class, 'register']);
    \$router->post('/auth/login', [AuthController::class, 'login']);
    \$router->post('/auth/logout', [AuthController::class, 'logout']);
    \$router->get('/auth/me', [AuthController::class, 'me']);
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

    public function verifyPassword(string \$password): bool
    {
        return password_verify(\$password, \$this->password);
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
    public function findById(string \$id): ?User;

    public function findByEmail(string \$email): ?User;

    public function save(User \$user): User;
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

interface AuthServiceInterface
{
    public function register(string \$email, string \$name, string \$password): User;

    public function login(string \$email, string \$password): ?string;

    public function getCurrentUser(string \$token): ?User;
}

PHP;
    }

    private function getDomainInvalidCredentialsExceptionContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Exception;

final class InvalidCredentialsException extends \\RuntimeException
{
}

PHP;
    }

    private function getDomainUserNotFoundExceptionContent(string $namespace, string $className): string
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

    private function getApplicationRegisterRequestDtoContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\DTO;

final class RegisterRequestDTO
{
    public function __construct(
        private ?string \$email = null,
        private ?string \$name = null,
        private ?string \$password = null,
    ) {}

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
}

PHP;
    }

    private function getApplicationLoginRequestDtoContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\DTO;

final class LoginRequestDTO
{
    public function __construct(
        private ?string \$email = null,
        private ?string \$password = null,
    ) {}

    public function getEmail(): ?string
    {
        return \$this->email;
    }

    public function getPassword(): ?string
    {
        return \$this->password;
    }
}

PHP;
    }

    private function getApplicationAuthResponseDtoContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\DTO;

final class AuthResponseDTO
{
    public function __construct(
        private string \$token,
        private string \$type = 'Bearer',
        private ?array \$user = null,
    ) {}

    public function getToken(): string
    {
        return \$this->token;
    }

    public function getType(): string
    {
        return \$this->type;
    }

    public function getUser(): ?array
    {
        return \$this->user;
    }

    public function toArray(): array
    {
        \$result = [
            'token' => \$this->token,
            'type' => \$this->type,
        ];

        if (\$this->user !== null) {
            \$result['user'] = \$this->user;
        }

        return \$result;
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

use {$namespace}\\Application\\DTO\\RegisterRequestDTO;
use {$namespace}\\Application\\DTO\\LoginRequestDTO;

final class AuthRequestValidator
{
    /**
     * @return string[] Hibák listája
     */
    public function validateForRegister(RegisterRequestDTO \$dto): array
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
    public function validateForLogin(LoginRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getEmail() === null || \$dto->getEmail() === '') {
            \$errors[] = 'Email is required';
        }

        if (\$dto->getPassword() === null || \$dto->getPassword() === '') {
            \$errors[] = 'Password is required';
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

use {$namespace}\\Application\\DTO\\RegisterRequestDTO;
use {$namespace}\\Application\\DTO\\LoginRequestDTO;
use {$namespace}\\Application\\Validator\\AuthRequestValidator;
use {$namespace}\\Domain\\Exception\\InvalidCredentialsException;
use {$namespace}\\Domain\\Exception\\UserNotFoundException;
use {$namespace}\\Domain\\Repository\\UserRepositoryInterface;
use {$namespace}\\Domain\\Service\\AuthServiceInterface;

final class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UserRepositoryInterface \$repository,
        private AuthRequestValidator \$validator,
    ) {}

    public function register(string \$email, string \$name, string \$password): \{$namespace}\\Domain\\Model\\User
    {
        \$dto = new RegisterRequestDTO(
            email: \$email,
            name: \$name,
            password: \$password
        );

        \$errors = \$this->validator->validateForRegister(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        // Check if user already exists
        \$existingUser = \$this->repository->findByEmail(\$email);
        if (\$existingUser !== null) {
            throw new \\RuntimeException('User with this email already exists');
        }

        \$user = new \{$namespace}\\Domain\\Model\\User(
            id: uniqid('user_', true),
            email: \$email,
            name: \$name,
            password: password_hash(\$password, PASSWORD_DEFAULT)
        );

        return \$this->repository->save(\$user);
    }

    public function login(string \$email, string \$password): ?string
    {
        \$dto = new LoginRequestDTO(
            email: \$email,
            password: \$password
        );

        \$errors = \$this->validator->validateForLogin(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$user = \$this->repository->findByEmail(\$email);
        if (\$user === null) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        if (!\$user->verifyPassword(\$password)) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        // Generate simple token (in production, use JWT or similar)
        return base64_encode(\$user->getId() . ':' . \$user->getEmail());
    }

    public function getCurrentUser(string \$token): ?\{$namespace}\\Domain\\Model\\User
    {
        // Decode token (in production, use JWT or similar)
        \$decoded = base64_decode(\$token, true);
        if (\$decoded === false) {
            return null;
        }

        \$parts = explode(':', \$decoded);
        if (count(\$parts) !== 2) {
            return null;
        }

        \$userId = \$parts[0];
        return \$this->repository->findById(\$userId);
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
final class AuthRequest
{
    public function __construct(
        private array \$queryParams = [],
        private array \$bodyParams = [],
        private array \$headers = [],
    ) {}

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

    public function getToken(): ?string
    {
        \$authHeader = \$this->headers['Authorization'] ?? \$this->headers['authorization'] ?? null;
        if (\$authHeader && str_starts_with(\$authHeader, 'Bearer ')) {
            return substr(\$authHeader, 7);
        }
        return null;
    }

    public function getAll(): array
    {
        return [
            'query' => \$this->queryParams,
            'body'  => \$this->bodyParams,
            'headers' => \$this->headers,
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

use {$namespace}\\Application\\DTO\\AuthResponseDTO;

final class AuthResponse
{
    public static function register(array \$user): array
    {
        return [
            'message' => 'User registered successfully',
            'user' => \$user,
        ];
    }

    public static function login(AuthResponseDTO \$dto): array
    {
        return \$dto->toArray();
    }

    public static function logout(): array
    {
        return [
            'message' => 'Logged out successfully',
        ];
    }

    public static function me(array \$user): array
    {
        return [
            'user' => \$user,
        ];
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

use {$namespace}\\Application\\Service\\AuthService;
use {$namespace}\\Presentation\\Http\\Request\\AuthRequest;
use {$namespace}\\Presentation\\Http\\Response\\AuthResponse;
use MAAF\\Core\\Http\\Response;

final class AuthController
{
    public function __construct(
        private AuthService \$service,
    ) {}

    public function register(AuthRequest \$request): array
    {
        \$user = \$this->service->register(
            \$request->getEmail() ?? '',
            \$request->getName() ?? '',
            \$request->getPassword() ?? ''
        );

        return AuthResponse::register([
            'id' => \$user->getId(),
            'email' => \$user->getEmail(),
            'name' => \$user->getName(),
        ]);
    }

    public function login(AuthRequest \$request): array
    {
        \$token = \$this->service->login(
            \$request->getEmail() ?? '',
            \$request->getPassword() ?? ''
        );

        return AuthResponse::login(new \{$namespace}\\Application\\DTO\\AuthResponseDTO(\$token));
    }

    public function logout(AuthRequest \$request): array
    {
        // TODO: Implement logout logic (token invalidation)
        return AuthResponse::logout();
    }

    public function me(AuthRequest \$request): Response
    {
        \$token = \$request->getToken();
        if (\$token === null) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        \$user = \$this->service->getCurrentUser(\$token);
        if (\$user === null) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        return Response::json(AuthResponse::me([
            'id' => \$user->getId(),
            'email' => \$user->getEmail(),
            'name' => \$user->getName(),
        ]));
    }
}

PHP;
    }
}
