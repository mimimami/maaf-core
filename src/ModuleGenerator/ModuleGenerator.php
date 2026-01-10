<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator;

/**
 * Module Generator
 * 
 * Modul generátor skeleton sablonokkal.
 * 
 * @version 1.0.0
 */
final class ModuleGenerator
{
    /**
     * @var array<string, ModuleTemplate>
     */
    private array $templates = [];

    public function __construct()
    {
        $this->registerDefaultTemplates();
    }

    /**
     * Register default templates
     * 
     * @return void
     */
    private function registerDefaultTemplates(): void
    {
        $this->templates['basic'] = new BasicModuleTemplate();
        $this->templates['api'] = new ApiModuleTemplate();
        $this->templates['crud'] = new CrudModuleTemplate();
        $this->templates['auth'] = new AuthModuleTemplate();
    }

    /**
     * Register a custom template
     * 
     * @param string $name Template name
     * @param ModuleTemplate $template Template instance
     * @return void
     */
    public function registerTemplate(string $name, ModuleTemplate $template): void
    {
        $this->templates[$name] = $template;
    }

    /**
     * Generate module
     * 
     * @param string $moduleName Module name
     * @param string $templateName Template name
     * @param string $basePath Base path for modules
     * @param ModuleMetadata $metadata Module metadata
     * @return ModuleGenerationResult
     */
    public function generate(
        string $moduleName,
        string $templateName,
        string $basePath,
        ModuleMetadata $metadata
    ): ModuleGenerationResult {
        if (!isset($this->templates[$templateName])) {
            throw new \InvalidArgumentException("Template '{$templateName}' not found");
        }

        $template = $this->templates[$templateName];
        $modulePath = rtrim($basePath, '/') . '/' . $moduleName;

        // Check if module already exists
        if (is_dir($modulePath)) {
            throw new \RuntimeException("Module '{$moduleName}' already exists at {$modulePath}");
        }

        // Create module directory
        if (!mkdir($modulePath, 0755, true)) {
            throw new \RuntimeException("Failed to create module directory: {$modulePath}");
        }

        $files = [];
        
        // Generate files from template
        foreach ($template->getFiles($metadata) as $file) {
            $filePath = $modulePath . '/' . $file->getPath();
            $fileDir = dirname($filePath);

            // Create directory if needed
            if (!is_dir($fileDir)) {
                mkdir($fileDir, 0755, true);
            }

            // Write file
            file_put_contents($filePath, $file->getContent());
            $files[] = $file->getPath();
        }

        return new ModuleGenerationResult($moduleName, $modulePath, $files);
    }

    /**
     * Get available templates
     * 
     * @return array<string>
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }
}
