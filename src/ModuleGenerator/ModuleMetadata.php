<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator;

/**
 * Module Metadata
 * 
 * Modul metaadatokat tartalmazó osztály.
 * 
 * @version 1.0.0
 */
final class ModuleMetadata
{
    public function __construct(
        public readonly string $name,
        public readonly string $namespace,
        public readonly string $description = '',
        public readonly string $version = '1.0.0',
        public readonly string $author = '',
        public readonly string $template = 'basic',
        public readonly array $dependencies = [],
        public readonly array $routes = [],
        public readonly array $services = []
    ) {
    }

    /**
     * Get full class name
     * 
     * @return string
     */
    public function getFullClassName(): string
    {
        return $this->namespace . '\\' . $this->name . '\\Module';
    }

    /**
     * Get controller namespace
     * 
     * @return string
     */
    public function getControllerNamespace(): string
    {
        return $this->namespace . '\\' . $this->name . '\\Controllers';
    }

    /**
     * Get service namespace
     * 
     * @return string
     */
    public function getServiceNamespace(): string
    {
        return $this->namespace . '\\' . $this->name . '\\Services';
    }

    /**
     * Get repository namespace
     * 
     * @return string
     */
    public function getRepositoryNamespace(): string
    {
        return $this->namespace . '\\' . $this->name . '\\Repositories';
    }

    /**
     * Get model namespace
     * 
     * @return string
     */
    public function getModelNamespace(): string
    {
        return $this->namespace . '\\' . $this->name . '\\Models';
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'namespace' => $this->namespace,
            'description' => $this->description,
            'version' => $this->version,
            'author' => $this->author,
            'template' => $this->template,
            'dependencies' => $this->dependencies,
            'routes' => $this->routes,
            'services' => $this->services,
        ];
    }
}
