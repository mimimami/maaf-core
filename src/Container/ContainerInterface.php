<?php

declare(strict_types=1);

namespace MAAF\Core\Container;

/**
 * Container Interface
 * 
 * Stabil API a Dependency Injection Container számára.
 * 
 * @version 1.0.0
 */
interface ContainerInterface
{
    /**
     * Get an entry from the container
     * 
     * @param string $id Entry identifier
     * @return mixed
     * @throws \MAAF\Core\Container\NotFoundException If the entry is not found
     */
    public function get(string $id): mixed;

    /**
     * Check if an entry exists in the container
     * 
     * @param string $id Entry identifier
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Set an entry in the container
     * 
     * @param string $id Entry identifier
     * @param mixed $value Entry value
     * @return void
     */
    public function set(string $id, mixed $value): void;

    /**
     * Make an instance of a class with dependency injection
     * 
     * @param string $class Class name
     * @param array<string, mixed> $parameters Optional parameters
     * @return object
     */
    public function make(string $class, array $parameters = []): object;
}
