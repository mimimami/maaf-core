<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * CRUD Module Template
 * 
 * CRUD modul sablon repository pattern-nel.
 * 
 * @version 1.0.0
 */
final class CrudModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'crud';
    }

    public function getDescription(): string
    {
        return 'CRUD module with repository pattern';
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

        // Repository
        $files[] = new GeneratedFile(
            'Repositories/' . $metadata->name . 'Repository.php',
            $this->generateRepositoryFile($metadata)
        );

        // Model
        $files[] = new GeneratedFile(
            'Models/' . $metadata->name . '.php',
            $this->generateModelFile($metadata)
        );

        return $files;
    }

    private function generateModuleFile(ModuleMetadata $metadata): string
    {
        $controllerClass = $metadata->getControllerNamespace() . '\\' . $metadata->name . 'Controller';
        $serviceClass = $metadata->getServiceNamespace() . '\\' . $metadata->name . 'Service';
        $repositoryClass = $metadata->getRepositoryNamespace() . '\\' . $metadata->name . 'Repository';
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
            {$repositoryClass}::class => DI\create({$repositoryClass}::class),
            {$serviceClass}::class => DI\create({$serviceClass}::class)
                ->constructor(DI\get({$repositoryClass}::class)),
        ]);
    }

    public static function registerRoutes(Router \$router): void
    {
        \$router->get('/{$routePrefix}', [
            {$controllerClass}::class,
            'index'
        ]);

        \$router->get('/{$routePrefix}/{id}', [
            {$controllerClass}::class,
            'show'
        ]);

        \$router->post('/{$routePrefix}', [
            {$controllerClass}::class,
            'create'
        ]);

        \$router->put('/{$routePrefix}/{id}', [
            {$controllerClass}::class,
            'update'
        ]);

        \$router->delete('/{$routePrefix}/{id}', [
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

    public function index(Request \$request): Response
    {
        \$items = \$this->service->getAll();
        return Response::json(['data' => \$items]);
    }

    public function show(Request \$request, string \$id): Response
    {
        \$item = \$this->service->getById((int) \$id);
        
        if (\$item === null) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        return Response::json(['data' => \$item]);
    }

    public function create(Request \$request): Response
    {
        \$data = \$request->getParsedBody();
        \$item = \$this->service->create(\$data);
        return Response::json(['data' => \$item], 201);
    }

    public function update(Request \$request, string \$id): Response
    {
        \$data = \$request->getParsedBody();
        \$item = \$this->service->update((int) \$id, \$data);
        
        if (\$item === null) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        return Response::json(['data' => \$item]);
    }

    public function delete(Request \$request, string \$id): Response
    {
        \$deleted = \$this->service->delete((int) \$id);
        
        if (!\$deleted) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        return Response::json(['message' => 'Deleted'], 204);
    }
}
PHP;
    }

    private function generateServiceFile(ModuleMetadata $metadata): string
    {
        $repositoryClass = $metadata->getRepositoryNamespace() . '\\' . $metadata->name . 'Repository';
        $modelClass = $metadata->getModelNamespace() . '\\' . $metadata->name;
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getServiceNamespace()};

use {$repositoryClass};
use {$modelClass};

/**
 * {$metadata->name} Service
 * 
{$this->generateDocComment($metadata)}
 */
final class {$metadata->name}Service
{
    public function __construct(
        private readonly {$metadata->name}Repository \$repository
    ) {
    }

    /**
     * @return array<int, {$metadata->name}>
     */
    public function getAll(): array
    {
        return \$this->repository->findAll();
    }

    public function getById(int \$id): ?{$metadata->name}
    {
        return \$this->repository->findById(\$id);
    }

    public function create(array \$data): {$metadata->name}
    {
        \$item = new {$metadata->name}();
        // TODO: Set properties from \$data
        return \$this->repository->save(\$item);
    }

    public function update(int \$id, array \$data): ?{$metadata->name}
    {
        \$item = \$this->repository->findById(\$id);
        
        if (\$item === null) {
            return null;
        }
        
        // TODO: Update properties from \$data
        return \$this->repository->save(\$item);
    }

    public function delete(int \$id): bool
    {
        return \$this->repository->delete(\$id);
    }
}
PHP;
    }

    private function generateRepositoryFile(ModuleMetadata $metadata): string
    {
        $modelClass = $metadata->getModelNamespace() . '\\' . $metadata->name;
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getRepositoryNamespace()};

use {$modelClass};

/**
 * {$metadata->name} Repository
 * 
{$this->generateDocComment($metadata)}
 */
final class {$metadata->name}Repository
{
    /**
     * @return array<int, {$metadata->name}>
     */
    public function findAll(): array
    {
        // TODO: Implement database query
        return [];
    }

    public function findById(int \$id): ?{$metadata->name}
    {
        // TODO: Implement database query
        return null;
    }

    public function save({$metadata->name} \$item): {$metadata->name}
    {
        // TODO: Implement save logic
        return \$item;
    }

    public function delete(int \$id): bool
    {
        // TODO: Implement delete logic
        return false;
    }
}
PHP;
    }

    private function generateModelFile(ModuleMetadata $metadata): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getModelNamespace()};

/**
 * {$metadata->name} Model
 * 
{$this->generateDocComment($metadata)}
 */
final class {$metadata->name}
{
    public int \$id;
    public string \$name;
    public \DateTimeImmutable \$createdAt;
    public \DateTimeImmutable \$updatedAt;

    public function __construct()
    {
        \$this->createdAt = new \DateTimeImmutable();
        \$this->updatedAt = new \DateTimeImmutable();
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
