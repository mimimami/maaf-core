<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleLoader;

use DI\ContainerBuilder;
use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\Routing\Router;

/**
 * Module Loader 3.0
 * 
 * Modul betöltő rendszer, amely automatikusan betölti és regisztrálja a modulokat.
 * 
 * @version 3.0.0
 */
final class ModuleLoader
{
    /**
     * @var array<string, ModuleInterface>
     */
    private array $modules = [];

    /**
     * @var array<string, mixed>
     */
    private array $moduleConfig = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly Router $router
    ) {
    }

    /**
     * Load modules from directory
     * 
     * @param string $modulesPath Path to modules directory
     * @param string $namespace Module namespace prefix
     * @param array<string, mixed> $config Module configuration
     * @return void
     */
    public function loadModules(string $modulesPath, string $namespace, array $config = []): void
    {
        $this->moduleConfig = $config;
        
        if (!is_dir($modulesPath)) {
            return;
        }

        $directories = array_filter(
            glob($modulesPath . '/*', GLOB_ONLYDIR),
            'is_dir'
        );

        foreach ($directories as $moduleDir) {
            $moduleName = basename($moduleDir);
            $moduleClass = $namespace . '\\' . $moduleName . '\\Module';
            
            if (class_exists($moduleClass)) {
                $this->loadModule($moduleClass, $moduleName);
            }
        }
    }

    /**
     * Load a single module
     * 
     * @param string $moduleClass Module class name
     * @param string $moduleName Module name
     * @return void
     */
    public function loadModule(string $moduleClass, string $moduleName): void
    {
        if (!class_exists($moduleClass)) {
            return;
        }

        if (!is_subclass_of($moduleClass, ModuleInterface::class)) {
            return;
        }

        $module = new $moduleClass();
        
        // Register services
        // Note: Service registration should be done during container initialization
        // Modules can register services via the services.php config file
        // or by implementing registerServices which will be called during bootstrap

        // Register routes
        if (method_exists($moduleClass, 'registerRoutes')) {
            $moduleClass::registerRoutes($this->router);
        }

        // Register event listeners if EventBus is available
        if (method_exists($moduleClass, 'registerEvents')) {
            if ($this->container->has(\MAAF\Core\EventBus\EventBusInterface::class)) {
                $eventBus = $this->container->get(\MAAF\Core\EventBus\EventBusInterface::class);
                $moduleClass::registerEvents($eventBus);
            }
        }

        $this->modules[$moduleName] = $module;
    }

    /**
     * Get all loaded modules
     * 
     * @return array<string, ModuleInterface>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Check if a module is loaded
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function hasModule(string $moduleName): bool
    {
        return isset($this->modules[$moduleName]);
    }

    /**
     * Get module configuration
     * 
     * @param string $moduleName Module name
     * @return array<string, mixed>
     */
    public function getModuleConfig(string $moduleName): array
    {
        return $this->moduleConfig[$moduleName] ?? [];
    }
}
