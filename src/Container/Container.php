<?php

declare(strict_types=1);

namespace MAAF\Core\Container;

use DI\Container as DIContainer;
use DI\ContainerBuilder;

/**
 * Container Implementation
 * 
 * Stabil DI Container implementáció PHP-DI alapján.
 * 
 * @version 1.0.0
 */
final class Container implements ContainerInterface
{
    private DIContainer $container;

    public function __construct(?DIContainer $container = null)
    {
        $this->container = $container ?? (new ContainerBuilder())->build();
    }

    /**
     * Create container from definitions
     * 
     * @param array<string, mixed>|string|callable $definitions Definitions
     * @return self
     */
    public static function fromDefinitions(array|string|callable $definitions): self
    {
        $builder = new ContainerBuilder();
        
        if (is_array($definitions)) {
            $builder->addDefinitions($definitions);
        } elseif (is_string($definitions) && file_exists($definitions)) {
            $builder->addDefinitions($definitions);
        } elseif (is_callable($definitions)) {
            $builder->addDefinitions($definitions);
        }
        
        return new self($builder->build());
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException($id);
        }
        
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function set(string $id, mixed $value): void
    {
        $this->container->set($id, $value);
    }

    public function make(string $class, array $parameters = []): object
    {
        return $this->container->make($class, $parameters);
    }

    /**
     * Get the underlying PHP-DI container
     * 
     * @return DIContainer
     */
    public function getInnerContainer(): DIContainer
    {
        return $this->container;
    }
}
