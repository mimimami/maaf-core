<?php

declare(strict_types=1);

namespace MAAF\Core\Module;

/**
 * Module Metadata
 * 
 * Stores information about a module.
 */
final class ModuleMetadata
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $namespace,
        public readonly ?string $version = null,
        public readonly array $dependencies = []
    ) {
    }
}

