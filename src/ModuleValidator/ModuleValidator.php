<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleValidator;

use MAAF\Core\ModuleLoader\ModuleInterface;

/**
 * Module Validator
 * 
 * Modul metaadat validátor.
 * 
 * @version 1.0.0
 */
final class ModuleValidator
{
    /**
     * Validate module name
     * 
     * @param string $name Module name
     * @return bool
     */
    public function validateModuleName(string $name): bool
    {
        // Must be PascalCase
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            return false;
        }

        // Must not contain reserved words
        $reservedWords = ['Module', 'Controller', 'Service', 'Repository', 'Model'];
        if (in_array($name, $reservedWords, true)) {
            return false;
        }

        return true;
    }

    /**
     * Validate module structure
     * 
     * @param string $modulePath Module directory path
     * @return ValidationResult
     */
    public function validateModuleStructure(string $modulePath): ValidationResult
    {
        $errors = [];
        $warnings = [];

        if (!is_dir($modulePath)) {
            return new ValidationResult(false, ['Module directory does not exist'], []);
        }

        // Check for Module.php
        $moduleFile = $modulePath . '/Module.php';
        if (!file_exists($moduleFile)) {
            $errors[] = 'Module.php file is missing';
        } else {
            // Validate Module class
            $moduleValidation = $this->validateModuleClass($moduleFile);
            if (!$moduleValidation->isValid()) {
                $errors = array_merge($errors, $moduleValidation->getErrors());
            }
            $warnings = array_merge($warnings, $moduleValidation->getWarnings());
        }

        // Check directory structure
        $requiredDirs = ['Controllers'];
        foreach ($requiredDirs as $dir) {
            $dirPath = $modulePath . '/' . $dir;
            if (!is_dir($dirPath)) {
                $warnings[] = "Directory '{$dir}' is missing (recommended)";
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate module class
     * 
     * @param string $moduleFile Module.php file path
     * @return ValidationResult
     */
    public function validateModuleClass(string $moduleFile): ValidationResult
    {
        $errors = [];
        $warnings = [];

        if (!file_exists($moduleFile)) {
            return new ValidationResult(false, ['Module file does not exist'], []);
        }

        // Read file content
        $content = file_get_contents($moduleFile);
        if ($content === false) {
            return new ValidationResult(false, ['Cannot read module file'], []);
        }

        // Check for ModuleInterface implementation
        if (!str_contains($content, 'implements ModuleInterface')) {
            $errors[] = 'Module class must implement ModuleInterface';
        }

        // Check for required methods
        $requiredMethods = [
            'registerServices',
            'registerRoutes',
        ];

        foreach ($requiredMethods as $method) {
            if (!str_contains($content, "function {$method}")) {
                $errors[] = "Required method '{$method}' is missing";
            }
        }

        // Check for proper namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $errors[] = 'Module namespace is missing or invalid';
        }

        // Check for strict types
        if (!str_contains($content, 'declare(strict_types=1);')) {
            $warnings[] = 'Module should declare strict types';
        }

        // Check for final class
        if (!str_contains($content, 'final class Module')) {
            $warnings[] = 'Module class should be final';
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate module metadata
     * 
     * @param array<string, mixed> $metadata Module metadata
     * @return ValidationResult
     */
    public function validateMetadata(array $metadata): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Required fields
        if (!isset($metadata['name']) || empty($metadata['name'])) {
            $errors[] = 'Module name is required';
        } elseif (!$this->validateModuleName($metadata['name'])) {
            $errors[] = 'Invalid module name format';
        }

        if (!isset($metadata['namespace']) || empty($metadata['namespace'])) {
            $errors[] = 'Module namespace is required';
        }

        // Optional fields validation
        if (isset($metadata['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $metadata['version'])) {
            $warnings[] = 'Version should follow semantic versioning (x.y.z)';
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Get module metadata from file
     * 
     * @param string $modulePath Module directory path
     * @return array<string, mixed>|null
     */
    public function extractMetadata(string $modulePath): ?array
    {
        $moduleFile = $modulePath . '/Module.php';
        
        if (!file_exists($moduleFile)) {
            return null;
        }

        $content = file_get_contents($moduleFile);
        if ($content === false) {
            return null;
        }

        $metadata = [];

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
            $metadata['namespace'] = $namespace;
            
            // Extract module name from namespace
            $parts = explode('\\', $namespace);
            if (count($parts) >= 2) {
                $metadata['name'] = end($parts);
            }
        }

        // Extract description from docblock
        if (preg_match('/\/\*\*\s*\n\s*\*\s*(.+?)\n/', $content, $matches)) {
            $metadata['description'] = trim($matches[1]);
        }

        // Extract version from docblock
        if (preg_match('/@version\s+([^\s]+)/', $content, $matches)) {
            $metadata['version'] = trim($matches[1]);
        }

        // Extract author from docblock
        if (preg_match('/@author\s+(.+)/', $content, $matches)) {
            $metadata['author'] = trim($matches[1]);
        }

        return $metadata;
    }
}
