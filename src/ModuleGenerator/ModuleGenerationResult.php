<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator;

/**
 * Module Generation Result
 * 
 * Modul generálás eredményét tartalmazza.
 * 
 * @version 1.0.0
 */
final class ModuleGenerationResult
{
    /**
     * @param string $name Module name
     * @param string $path Module path
     * @param array<int, string> $files Generated files
     */
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly array $files
    ) {
    }

    /**
     * Get module name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get module path
     * 
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get generated files
     * 
     * @return array<int, string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
