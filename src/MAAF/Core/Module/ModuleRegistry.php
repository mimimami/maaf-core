<?php

declare(strict_types=1);

namespace MAAF\Core\Module;

/**
 * Module Registry
 * 
 * Stores metadata about discovered modules.
 */
final class ModuleRegistry
{
    /**
     * @var array<string, ModuleMetadata>
     */
    private array $modules = [];

    /**
     * Register a module with its metadata.
     */
    public function register(string $name, ModuleMetadata $metadata): void
    {
        $this->modules[$name] = $metadata;
    }

    /**
     * Get module metadata by name.
     */
    public function get(string $name): ?ModuleMetadata
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Check if a module is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Get all registered modules.
     *
     * @return array<string, ModuleMetadata>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Get all module names.
     *
     * @return array<string>
     */
    public function names(): array
    {
        return array_keys($this->modules);
    }
}

