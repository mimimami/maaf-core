<?php

declare(strict_types=1);

namespace MAAF\Core\Cli\Commands;

use MAAF\Core\Cli\CommandInterface;
use MAAF\Core\ModuleGenerator\ModuleGenerator;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;
use MAAF\Core\ModuleValidator\ModuleValidator;

/**
 * Make Module Command
 * 
 * Létrehoz egy új modult skeleton sablonokkal.
 * 
 * @version 1.0.0
 */
final class MakeModuleCommand implements CommandInterface
{
    public function __construct(
        private readonly ModuleGenerator $generator,
        private readonly ModuleValidator $validator
    ) {
    }

    public function getName(): string
    {
        return 'make:module';
    }

    public function getDescription(): string
    {
        return 'Create a new module with skeleton templates';
    }

    public function execute(array $args): int
    {
        $moduleName = $args[0] ?? null;

        if ($moduleName === null) {
            echo "❌ Module name required\n";
            echo "Usage: php maaf make:module ModuleName [options]\n";
            echo "\nOptions:\n";
            echo "  --template=TEMPLATE    Template type (api, crud, auth, basic)\n";
            echo "  --namespace=NS         Module namespace (default: App\\Modules)\n";
            echo "  --path=PATH            Modules directory path\n";
            echo "  --interactive          Interactive mode\n";
            return 1;
        }

        // Validate module name
        if (!$this->validator->validateModuleName($moduleName)) {
            echo "❌ Invalid module name: {$moduleName}\n";
            echo "Module name must be PascalCase and contain only letters.\n";
            return 1;
        }

        // Parse options
        $options = $this->parseOptions($args);
        
        // Interactive mode
        if ($options['interactive'] ?? false) {
            return $this->interactiveMode($moduleName, $options);
        }

        // Non-interactive mode
        return $this->generateModule($moduleName, $options);
    }

    /**
     * Interactive mode
     * 
     * @param string $moduleName Module name
     * @param array<string, mixed> $options Options
     * @return int
     */
    private function interactiveMode(string $moduleName, array $options): int
    {
        echo "Creating module: {$moduleName}\n";
        echo str_repeat("=", 50) . "\n\n";

        // Template selection
        $templates = ['basic', 'api', 'crud', 'auth'];
        echo "Available templates:\n";
        foreach ($templates as $i => $template) {
            echo "  " . ($i + 1) . ". {$template}\n";
        }
        
        echo "\nSelect template [1-4] (default: basic): ";
        $templateInput = trim(fgets(STDIN) ?: '1');
        $templateIndex = (int) $templateInput - 1;
        
        if ($templateIndex < 0 || $templateIndex >= count($templates)) {
            $templateIndex = 0;
        }
        
        $template = $templates[$templateIndex];
        $options['template'] = $template;

        // Namespace
        if (!isset($options['namespace'])) {
            echo "Module namespace [App\\Modules]: ";
            $namespace = trim(fgets(STDIN) ?: 'App\\Modules');
            $options['namespace'] = $namespace ?: 'App\\Modules';
        }

        // Path
        if (!isset($options['path'])) {
            echo "Modules directory path [src/Modules]: ";
            $path = trim(fgets(STDIN) ?: 'src/Modules');
            $options['path'] = $path ?: 'src/Modules';
        }

        // Metadata
        echo "\nModule metadata (optional):\n";
        echo "Description: ";
        $description = trim(fgets(STDIN) ?: '');
        if ($description) {
            $options['description'] = $description;
        }

        echo "Version [1.0.0]: ";
        $version = trim(fgets(STDIN) ?: '1.0.0');
        $options['version'] = $version ?: '1.0.0';

        echo "Author: ";
        $author = trim(fgets(STDIN) ?: '');
        if ($author) {
            $options['author'] = $author;
        }

        echo "\n";

        return $this->generateModule($moduleName, $options);
    }

    /**
     * Generate module
     * 
     * @param string $moduleName Module name
     * @param array<string, mixed> $options Options
     * @return int
     */
    private function generateModule(string $moduleName, array $options): int
    {
        $template = $options['template'] ?? 'basic';
        $namespace = $options['namespace'] ?? 'App\\Modules';
        $path = $options['path'] ?? 'src/Modules';

        // Create metadata
        $metadata = new ModuleMetadata(
            name: $moduleName,
            namespace: $namespace,
            description: $options['description'] ?? '',
            version: $options['version'] ?? '1.0.0',
            author: $options['author'] ?? '',
            template: $template
        );

        try {
            $result = $this->generator->generate($moduleName, $template, $path, $metadata);

            echo "✅ Module created successfully!\n\n";
            echo "Module: {$moduleName}\n";
            echo "Template: {$template}\n";
            echo "Location: {$result->getPath()}\n";
            echo "\nFiles created:\n";
            
            foreach ($result->getFiles() as $file) {
                echo "  ✓ {$file}\n";
            }

            echo "\nNext steps:\n";
            echo "  1. Register routes in {$moduleName}/Module.php\n";
            echo "  2. Implement your controllers\n";
            echo "  3. Add services if needed\n";

            return 0;
        } catch (\Exception $e) {
            echo "❌ Error creating module: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Parse command line options
     * 
     * @param array<string> $args Arguments
     * @return array<string, mixed>
     */
    private function parseOptions(array $args): array
    {
        $options = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                $value = $parts[1] ?? true;

                if ($key === 'template') {
                    $options['template'] = $value;
                } elseif ($key === 'namespace') {
                    $options['namespace'] = $value;
                } elseif ($key === 'path') {
                    $options['path'] = $value;
                } elseif ($key === 'interactive') {
                    $options['interactive'] = true;
                } elseif ($key === 'description') {
                    $options['description'] = $value;
                } elseif ($key === 'version') {
                    $options['version'] = $value;
                } elseif ($key === 'author') {
                    $options['author'] = $value;
                }
            }
        }

        return $options;
    }
}
