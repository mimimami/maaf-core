<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator;

/**
 * Generated File
 * 
 * Generált fájl információit tartalmazza.
 * 
 * @version 1.0.0
 */
final class GeneratedFile
{
    public function __construct(
        private readonly string $path,
        private readonly string $content
    ) {
    }

    /**
     * Get file path
     * 
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get file content
     * 
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
}
