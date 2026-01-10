<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator;

/**
 * Module Template Interface
 * 
 * Interface a modul sablonok számára.
 * 
 * @version 1.0.0
 */
interface ModuleTemplate
{
    /**
     * Get template name
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Get template description
     * 
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get files to generate
     * 
     * @param ModuleMetadata $metadata Module metadata
     * @return array<int, GeneratedFile>
     */
    public function getFiles(ModuleMetadata $metadata): array;
}
