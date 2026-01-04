<?php

declare(strict_types=1);

namespace MAAF\Core\DI;

use DI\ContainerBuilder;

/**
 * Container Factory
 * 
 * Helper for creating DI containers.
 */
final class ContainerFactory
{
    /**
     * Create a container from a definitions file.
     */
    public static function create(string $definitionsFile): \DI\Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitionsFile);
        return $builder->build();
    }

    /**
     * Create a container builder.
     */
    public static function builder(): ContainerBuilder
    {
        return new ContainerBuilder();
    }
}

