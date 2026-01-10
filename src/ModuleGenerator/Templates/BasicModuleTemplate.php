<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * Basic Module Template
 * 
 * Alap modul sablon.
 * 
 * @version 1.0.0
 */
final class BasicModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'basic';
    }

    public function getDescription(): string
    {
        return 'Basic module with controller and routes';
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

        return $files;
    }

    private function generateModuleFile(ModuleMetadata $metadata): string
    {
        $controllerClass = $metadata->getControllerNamespace() . '\\' . $metadata->name . 'Controller';
        
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
        // Register services here
    }

    public static function registerRoutes(Router \$router): void
    {
        \$router->get('/{$this->getRoutePrefix($metadata->name)}', [
            {$controllerClass}::class,
            'index'
        ]);
    }
}
PHP;
    }

    private function generateControllerFile(ModuleMetadata $metadata): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$metadata->getControllerNamespace()};

use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

/**
 * {$metadata->name} Controller
 * 
{$this->generateDocComment($metadata)}
 */
final class {$metadata->name}Controller
{
    public function index(Request \$request): Response
    {
        return Response::json([
            'message' => 'Hello from {$metadata->name}',
            'module' => '{$metadata->name}',
            'version' => '{$metadata->version}',
        ]);
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
