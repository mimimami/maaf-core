<?php

declare(strict_types=1);

namespace MAAF\Core\Cli;

use MAAF\Core\Cli\Commands\EventConsumeCommand;
use MAAF\Core\Cli\Commands\HelpCommand;
use MAAF\Core\Cli\Commands\ListRoutesCommand;
use MAAF\Core\Cli\Commands\MakeModuleCommand;
use MAAF\Core\Cli\Commands\ValidateModuleCommand;
use MAAF\Core\Config\Config;
use MAAF\Core\Config\ConfigInterface;
use MAAF\Core\Container\Container;
use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\ModuleGenerator\ModuleGenerator;
use MAAF\Core\ModuleLoader\ModuleLoader;
use MAAF\Core\ModuleValidator\ModuleValidator;
use MAAF\Core\Routing\Router;
use ReflectionClass;

/**
 * CLI Application
 * 
 * Fő CLI alkalmazás osztály, amely kezeli a CLI parancsokat és az autodiscovery-t.
 * 
 * @version 1.0.0
 */
final class Application
{
    private ContainerInterface $container;
    private ConfigInterface $config;
    private Cli $cli;
    private ?ModuleLoader $moduleLoader = null;

    public function __construct(?string $basePath = null)
    {
        $basePath = $basePath ?? $this->detectBasePath();
        
        // Initialize container
        $this->container = Container::fromDefinitions([]);
        
        // Initialize config
        $this->config = new Config();
        
        // Load application config
        $configFile = $basePath . '/config/maaf.php';
        if (file_exists($configFile)) {
            $this->config->loadFromFile($configFile);
        }

        // Initialize router
        $router = new Router();
        $this->container->set(Router::class, $router);

        // Try to register Runtime service if available
        if (class_exists(\MAAF\Runtime\Runtime::class)) {
            try {
                // Runtime requires Kernel, so we need to create it first
                if (class_exists(\MAAF\Core\Http\Kernel::class)) {
                    $kernel = new \MAAF\Core\Http\Kernel($this->container, $router);
                    $this->container->set(\MAAF\Core\Http\Kernel::class, $kernel);
                    
                    $runtime = new \MAAF\Runtime\Runtime(
                        $this->container,
                        $kernel,
                        $this->config->get('runtime.cache', true),
                        $this->config->get('runtime.preload', true)
                    );
                    $this->container->set(\MAAF\Runtime\Runtime::class, $runtime);
                }
            } catch (\Throwable $e) {
                // Runtime not available, skip silently
            }
        }

        // Try to register QueueManager service if available
        if (class_exists(\MAAF\Queue\QueueManager::class)) {
            try {
                $queueManager = new \MAAF\Queue\QueueManager();
                $this->container->set(\MAAF\Queue\QueueManager::class, $queueManager);
                
                // Try to register ModuleWorkerManager if available
                if (class_exists(\MAAF\Queue\Worker\ModuleWorkerManager::class)) {
                    $workerManager = new \MAAF\Queue\Worker\ModuleWorkerManager($queueManager);
                    $this->container->set(\MAAF\Queue\Worker\ModuleWorkerManager::class, $workerManager);
                }
                
                // Try to register Dashboard if available
                if (class_exists(\MAAF\Queue\Dashboard\Dashboard::class)) {
                    $dashboard = new \MAAF\Queue\Dashboard\Dashboard($queueManager);
                    $this->container->set(\MAAF\Queue\Dashboard\Dashboard::class, $dashboard);
                }
            } catch (\Throwable $e) {
                // QueueManager not available, skip silently
            }
        }

        // Load modules
        $modulesPath = $this->config->get('modules.path');
        $modulesNamespace = $this->config->get('modules.namespace');
        
        if ($modulesPath && $modulesNamespace) {
            $modulesPath = str_starts_with($modulesPath, '/') 
                ? $modulesPath 
                : $basePath . '/' . $modulesPath;
            
            $this->moduleLoader = new ModuleLoader($this->container, $router);
            $this->moduleLoader->loadModules($modulesPath, $modulesNamespace);
        }

        // Initialize CLI
        $this->cli = new Cli($this->container);
        
        // Register built-in commands
        $this->registerBuiltInCommands();
        
        // Register runtime commands
        $this->registerRuntimeCommands();
        
        // Register queue commands
        $this->registerQueueCommands();
        
        // Register tenant commands (if any)
        $this->registerTenantCommands();
        
        // Register module commands
        $this->registerModuleCommands();
    }

    /**
     * Detect base path
     * 
     * @return string
     */
    private function detectBasePath(): string
    {
        // Try to find composer.json in parent directories
        $dir = __DIR__;
        $maxDepth = 10;
        $depth = 0;
        
        while ($depth < $maxDepth) {
            if (file_exists($dir . '/composer.json')) {
                // Check if it's a project (not a library)
                $composer = json_decode(file_get_contents($dir . '/composer.json'), true);
                if (isset($composer['type']) && $composer['type'] === 'project') {
                    return $dir;
                }
            }
            
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
            $depth++;
        }
        
        // Fallback to current working directory
        return getcwd() ?: __DIR__;
    }

    /**
     * Register built-in commands
     * 
     * @return void
     */
    private function registerBuiltInCommands(): void
    {
        // Built-in commands are already registered by Cli constructor
        // But we can add additional ones here if needed
    }

    /**
     * Register runtime commands
     * 
     * @return void
     */
    private function registerRuntimeCommands(): void
    {
        $runtimeCommands = [
            \MAAF\Runtime\CLI\Commands\RuntimeStartCommand::class,
            \MAAF\Runtime\CLI\Commands\RuntimeStopCommand::class,
            \MAAF\Runtime\CLI\Commands\RuntimeReloadCommand::class,
            \MAAF\Runtime\CLI\Commands\CacheClearCommand::class,
            \MAAF\Runtime\CLI\Commands\PreloadGenerateCommand::class,
        ];

        foreach ($runtimeCommands as $commandClass) {
            if (class_exists($commandClass)) {
                $this->registerCommand($commandClass);
            }
        }
    }

    /**
     * Register queue commands
     * 
     * @return void
     */
    private function registerQueueCommands(): void
    {
        $queueCommands = [
            \MAAF\Queue\CLI\Commands\QueueWorkCommand::class,
            \MAAF\Queue\CLI\Commands\QueueListenCommand::class,
            \MAAF\Queue\CLI\Commands\QueueDashboardCommand::class,
        ];

        foreach ($queueCommands as $commandClass) {
            if (class_exists($commandClass)) {
                $this->registerCommand($commandClass);
            }
        }
    }

    /**
     * Register tenant commands
     * 
     * @return void
     */
    private function registerTenantCommands(): void
    {
        // Tenant commands can be registered here if they exist
        // For now, tenant doesn't have CLI commands, but this is a placeholder
    }

    /**
     * Register module commands
     * 
     * @return void
     */
    private function registerModuleCommands(): void
    {
        if ($this->moduleLoader === null) {
            return;
        }

        $modules = $this->moduleLoader->getModules();
        
        foreach ($modules as $moduleName => $module) {
            // Check if module has a getCommands method
            if (method_exists($module, 'getCommands')) {
                $commands = $module->getCommands();
                if (is_array($commands)) {
                    foreach ($commands as $command) {
                        if ($command instanceof CommandInterface) {
                            $this->cli->register($command);
                        }
                    }
                }
            }
            
            // Also try to discover commands in module's CLI/Commands directory
            $this->discoverCommandsInModule($moduleName);
        }
    }

    /**
     * Discover commands in a module directory
     * 
     * @param string $moduleName Module name
     * @return void
     */
    private function discoverCommandsInModule(string $moduleName): void
    {
        $modulesPath = $this->config->get('modules.path');
        $modulesNamespace = $this->config->get('modules.namespace');
        
        if (!$modulesPath || !$modulesNamespace) {
            return;
        }

        $basePath = $this->detectBasePath();
        $modulesPath = str_starts_with($modulesPath, '/') 
            ? $modulesPath 
            : $basePath . '/' . $modulesPath;
        
        $moduleDir = $modulesPath . '/' . $moduleName;
        $commandsDir = $moduleDir . '/CLI/Commands';
        
        if (!is_dir($commandsDir)) {
            return;
        }

        $files = glob($commandsDir . '/*.php');
        
        foreach ($files as $file) {
            $className = $modulesNamespace . '\\' . $moduleName . '\\CLI\\Commands\\' . basename($file, '.php');
            
            if (class_exists($className)) {
                $this->registerCommand($className);
            }
        }
    }

    /**
     * Register a command class
     * 
     * @param string $commandClass Command class name
     * @return void
     */
    private function registerCommand(string $commandClass): void
    {
        if (!class_exists($commandClass)) {
            return;
        }

        $reflection = new ReflectionClass($commandClass);
        
        if (!$reflection->implementsInterface(CommandInterface::class)) {
            return;
        }

        if ($reflection->isAbstract() || $reflection->isInterface()) {
            return;
        }

        // Try to instantiate the command
        // Commands may require dependencies, so we try to resolve them from container
        try {
            $command = $this->instantiateCommand($commandClass, $reflection);
            if ($command instanceof CommandInterface) {
                $this->cli->register($command);
            }
        } catch (\Throwable $e) {
            // Silently skip commands that can't be instantiated
            // This allows optional dependencies
        }
    }

    /**
     * Instantiate a command with dependency injection
     * 
     * @param string $commandClass Command class name
     * @param ReflectionClass $reflection Reflection class
     * @return CommandInterface|null
     */
    private function instantiateCommand(string $commandClass, ReflectionClass $reflection): ?CommandInterface
    {
        $constructor = $reflection->getConstructor();
        
        if ($constructor === null) {
            // No constructor, instantiate directly
            return new $commandClass();
        }

        $parameters = $constructor->getParameters();
        $args = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            if ($type === null) {
                // No type hint, use null if optional
                $args[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                continue;
            }

            if (!$type instanceof \ReflectionNamedType) {
                // Union or intersection type, skip
                $args[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                continue;
            }

            $typeName = $type->getName();
            
            // Try to get from container
            if ($this->container->has($typeName)) {
                $args[] = $this->container->get($typeName);
            } elseif (class_exists($typeName)) {
                // Try to instantiate if it's a class
                try {
                    $args[] = $this->container->make($typeName);
                } catch (\Throwable $e) {
                    // Use default value or null
                    $args[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                }
            } else {
                // Use default value or null
                $args[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }
        }

        return new $commandClass(...$args);
    }

    /**
     * Run the CLI application
     * 
     * @return int Exit code
     */
    public function run(): int
    {
        global $argv;
        return $this->cli->run($argv ?? []);
    }

    /**
     * Get CLI instance
     * 
     * @return Cli
     */
    public function getCli(): Cli
    {
        return $this->cli;
    }

    /**
     * Get container
     * 
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
