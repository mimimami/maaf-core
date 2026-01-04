<?php

declare(strict_types=1);

namespace MAAF\Core\Module;

use DI\ContainerBuilder;

/**
 * Module Loader
 * 
 * Discovers modules and registers their services and routes.
 */
final class ModuleLoader
{
    /**
     * @param string $modulesPath Path to modules directory
     * @param ModuleRegistry $registry Module registry
     * @param string $namespacePrefix Namespace prefix for modules (e.g., "App\\Modules")
     */
    public function __construct(
        private string $modulesPath,
        private ModuleRegistry $registry,
        private string $namespacePrefix = 'App\\Modules'
    ) {
    }

    /**
     * Discover all modules in the modules directory.
     */
    public function discover(): void
    {
        if (!is_dir($this->modulesPath)) {
            return;
        }

        $directories = scandir($this->modulesPath);
        if ($directories === false) {
            return;
        }

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $modulePath = $this->modulesPath . '/' . $dir;
            if (!is_dir($modulePath)) {
                continue;
            }

            $moduleName = $dir;
            $moduleNamespace = $this->namespacePrefix . '\\' . $moduleName;
            $moduleClass = $moduleNamespace . '\\Module';

            // Check if Module.php exists
            $moduleFile = $modulePath . '/Module.php';
            if (!file_exists($moduleFile)) {
                continue;
            }

            // Register module metadata
            $metadata = new ModuleMetadata(
                name: $moduleName,
                path: $modulePath,
                namespace: $moduleNamespace
            );

            $this->registry->register($moduleName, $metadata);
        }
    }

    /**
     * Register services from all discovered modules.
     */
    public function registerServices(ContainerBuilder $builder): void
    {
        foreach ($this->registry->all() as $metadata) {
            $moduleClass = $metadata->namespace . '\\Module';

            if (!class_exists($moduleClass)) {
                continue;
            }

            // Check if module has registerServices method
            if (method_exists($moduleClass, 'registerServices')) {
                $moduleClass::registerServices($builder);
            }
        }
    }

    /**
     * Register routes from all discovered modules.
     * 
     * @param \MAAF\Core\Routing\Router|callable $router
     */
    public function registerRoutes($router): void
    {
        foreach ($this->registry->all() as $metadata) {
            $moduleClass = $metadata->namespace . '\\Module';

            if (!class_exists($moduleClass)) {
                continue;
            }

            // Check if module has registerRoutes method
            if (method_exists($moduleClass, 'registerRoutes')) {
                $moduleClass::registerRoutes($router);
            }
        }
    }

    /**
     * Get the module registry.
     */
    public function getRegistry(): ModuleRegistry
    {
        return $this->registry;
    }
}

