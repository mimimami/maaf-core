<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * API Module Template
 * 
 * API modul sablon RESTful végpontokkal.
 * 
 * @version 1.0.0
 */
final class ApiModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'api';
    }

    public function getDescription(): string
    {
        return 'API module with RESTful endpoints';
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
            'Controllers/' . $metadata->name . 'Controller.php',
            $this->generateControllerFile($metadata)
        );

        // Service
        $files[] = new GeneratedFile(
            'Services/' . $metadata->name . 'Service.php',
            $this->generateServiceFile($metadata)
        );

        return $files;
    }

    private function generateModuleFile(ModuleMetadata $metadata): string
    {
        $controllerClass = $metadata->getControllerNamespace() . '\\' . $metadata->name . 'Controller';
        $serviceClass = $metadata->getServiceNamespace() . '\\' . $metadata->name . 'Service';
        $routePrefix = $this->getRoutePrefix($metadata->name);
        
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
        // List all
        \$router->get('/api/{$routePrefix}', [
            {$controllerClass}::class,
            'index'
        ]);

        // Get by ID
        \$router->get('/api/{$routePrefix}/{id}', [
            {$controllerClass}::class,
            'show'
        ]);

        // Create
        \$router->post('/api/{$routePrefix}', [
            {$controllerClass}::class,
            'create'
        ]);

        // Update
        \$router->put('/api/{$routePrefix}/{id}', [
            {$controllerClass}::class,
            'update'
        ]);

        // Delete
        \$router->delete('/api/{$routePrefix}/{id}', [
            {$controllerClass}::class,
            'delete'
        ]);
    }
}
PHP;
    }

    private function generateControllerFile(ModuleMetadata $metadata): string
    {
        $serviceClass = $metadata->getServiceNamespace() . '\\' . $metadata->name . 'Service';
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getControllerNamespace()};

use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;
use {$serviceClass};

/**
 * {$metadata->name} Controller
 * 
{$this->generateDocComment($metadata)}
 */
final class {$metadata->name}Controller
{
    public function __construct(
        private readonly {$metadata->name}Service \$service
    ) {
    }

    /**
     * List all items
     */
    public function index(Request \$request): Response
    {
        \$items = \$this->service->getAll();
        
        return Response::json([
            'data' => \$items,
            'count' => count(\$items),
        ]);
    }

    /**
     * Get item by ID
     */
    public function show(Request \$request, string \$id): Response
    {
        \$item = \$this->service->getById(\$id);
        
        if (\$item === null) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        return Response::json(['data' => \$item]);
    }

    /**
     * Create new item
     */
    public function create(Request \$request): Response
    {
        \$data = \$request->getParsedBody();
        \$item = \$this->service->create(\$data);
        
        return Response::json(['data' => \$item], 201);
    }

    /**
     * Update item
     */
    public function update(Request \$request, string \$id): Response
    {
        \$data = \$request->getParsedBody();
        \$item = \$this->service->update(\$id, \$data);
        
        if (\$item === null) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        return Response::json(['data' => \$item]);
    }

    /**
     * Delete item
     */
    public function delete(Request \$request, string \$id): Response
    {
        \$deleted = \$this->service->delete(\$id);
        
        if (!\$deleted) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        return Response::json(['message' => 'Deleted successfully'], 204);
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
 * {$metadata->name} Service
 * 
{$this->generateDocComment($metadata)}
 */
final class {$metadata->name}Service
{
    /**
     * Get all items
     * 
     * @return array<int, mixed>
     */
    public function getAll(): array
    {
        // TODO: Implement
        return [];
    }

    /**
     * Get item by ID
     * 
     * @param string \$id Item ID
     * @return mixed|null
     */
    public function getById(string \$id): mixed
    {
        // TODO: Implement
        return null;
    }

    /**
     * Create new item
     * 
     * @param array<string, mixed> \$data Item data
     * @return mixed
     */
    public function create(array \$data): mixed
    {
        // TODO: Implement
        return \$data;
    }

    /**
     * Update item
     * 
     * @param string \$id Item ID
     * @param array<string, mixed> \$data Item data
     * @return mixed|null
     */
    public function update(string \$id, array \$data): mixed
    {
        // TODO: Implement
        return null;
    }

    /**
     * Delete item
     * 
     * @param string \$id Item ID
     * @return bool
     */
    public function delete(string \$id): bool
    {
        // TODO: Implement
        return false;
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

    private function getRoutePrefix(string $moduleName): string
    {
        return strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst($moduleName)));
    }
}
