<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleLoader;

use DI\ContainerBuilder;
use MAAF\Core\Routing\Router;

/**
 * Module Interface
 * 
 * Interface that all modules must implement.
 * 
 * @version 3.0.0
 */
interface ModuleInterface
{
    /**
     * Register services in the DI container
     * 
     * @param ContainerBuilder $builder Container builder
     * @return void
     */
    public static function registerServices(ContainerBuilder $builder): void;

    /**
     * Register routes
     * 
     * @param Router $router Router instance
     * @return void
     */
    public static function registerRoutes(Router $router): void;
}
