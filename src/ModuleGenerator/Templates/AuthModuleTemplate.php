<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * Auth Module Template
 * 
 * Authentication modul sablon.
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
        return 'Authentication module with login, register, logout';
    }

    public function getFiles(ModuleMetadata $metadata): array
    {
        $files = [];

        // Module.php
        $files[] = new GeneratedFile(
            'Module.php',
            $this->generateModuleFile($metadata)
        );

        // Controller
        $files[] = new GeneratedFile(
            'Controllers/AuthController.php',
            $this->generateControllerFile($metadata)
        );

        // Service
        $files[] = new GeneratedFile(
            'Services/AuthService.php',
            $this->generateServiceFile($metadata)
        );

        return $files;
    }

    private function generateModuleFile(ModuleMetadata $metadata): string
    {
        $controllerClass = $metadata->getControllerNamespace() . '\\AuthController';
        $serviceClass = $metadata->getServiceNamespace() . '\\AuthService';
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getFullClassName()};

use DI\ContainerBuilder;
use MAAF\Core\ModuleLoader\ModuleInterface;
use MAAF\Core\Routing\Router;

/**
 * {$metadata->name} Module
 * 
{$this->generateDocComment($metadata)}
 */
final class Module implements ModuleInterface
{
    public static function registerServices(ContainerBuilder \$builder): void
    {
        \$builder->addDefinitions([
            {$serviceClass}::class => DI\create({$serviceClass}::class),
        ]);
    }

    public static function registerRoutes(Router \$router): void
    {
        \$router->post('/auth/register', [
            {$controllerClass}::class,
            'register'
        ]);

        \$router->post('/auth/login', [
            {$controllerClass}::class,
            'login'
        ]);

        \$router->post('/auth/logout', [
            {$controllerClass}::class,
            'logout'
        ]);

        \$router->get('/auth/me', [
            {$controllerClass}::class,
            'me'
        ]);
    }
}
PHP;
    }

    private function generateControllerFile(ModuleMetadata $metadata): string
    {
        $serviceClass = $metadata->getServiceNamespace() . '\\AuthService';
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getControllerNamespace()};

use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;
use {$serviceClass};

/**
 * Auth Controller
 * 
{$this->generateDocComment($metadata)}
 */
final class AuthController
{
    public function __construct(
        private readonly AuthService \$service
    ) {
    }

    public function register(Request \$request): Response
    {
        \$data = \$request->getParsedBody();
        
        // TODO: Validate input
        \$user = \$this->service->register(\$data);
        
        return Response::json([
            'message' => 'User registered successfully',
            'user' => \$user,
        ], 201);
    }

    public function login(Request \$request): Response
    {
        \$data = \$request->getParsedBody();
        
        // TODO: Validate input
        \$token = \$this->service->login(\$data['email'], \$data['password']);
        
        if (\$token === null) {
            return Response::json(['error' => 'Invalid credentials'], 401);
        }
        
        return Response::json([
            'token' => \$token,
            'type' => 'Bearer',
        ]);
    }

    public function logout(Request \$request): Response
    {
        // TODO: Implement logout logic (token invalidation)
        return Response::json(['message' => 'Logged out successfully']);
    }

    public function me(Request \$request): Response
    {
        // TODO: Get user from token
        \$user = \$this->service->getCurrentUser(\$request);
        
        if (\$user === null) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        return Response::json(['user' => \$user]);
    }
}
PHP;
    }

    private function generateServiceFile(ModuleMetadata $metadata): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getServiceNamespace()};

/**
 * Auth Service
 * 
{$this->generateDocComment($metadata)}
 */
final class AuthService
{
    public function register(array \$data): array
    {
        // TODO: Implement registration logic
        // - Validate input
        // - Hash password
        // - Create user
        // - Return user data
        return [];
    }

    public function login(string \$email, string \$password): ?string
    {
        // TODO: Implement login logic
        // - Find user by email
        // - Verify password
        // - Generate JWT token
        // - Return token
        return null;
    }

    public function getCurrentUser(\$request): ?array
    {
        // TODO: Implement get current user logic
        // - Extract token from request
        // - Validate token
        // - Return user data
        return null;
    }
}
PHP;
    }

    private function generateDocComment(ModuleMetadata $metadata): string
    {
        $lines = [];
        
        if ($metadata->description) {
            $lines[] = ' * ' . $metadata->description;
        }
        
        if ($metadata->version) {
            $lines[] = ' * ';
            $lines[] = ' * @version ' . $metadata->version;
        }
        
        if ($metadata->author) {
            $lines[] = ' * @author ' . $metadata->author;
        }

        return implode("\n", $lines);
    }
}
