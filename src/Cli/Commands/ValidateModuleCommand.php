<?php

declare(strict_types=1);

namespace MAAF\Core\Cli\Commands;

use MAAF\Core\Cli\CommandInterface;
use MAAF\Core\ModuleValidator\ModuleValidator;

/**
 * Validate Module Command
 * 
 * Validálja a modul struktúráját és metaadatait.
 * 
 * @version 1.0.0
 */
final class ValidateModuleCommand implements CommandInterface
{
    public function __construct(
        private readonly ModuleValidator $validator
    ) {
    }

    public function getName(): string
    {
        return 'module:validate';
    }

    public function getDescription(): string
    {
        return 'Validate module structure and metadata';
    }

    public function execute(array $args): int
    {
        $modulePath = $args[0] ?? null;

        if ($modulePath === null) {
            echo "❌ Module path required\n";
            echo "Usage: php maaf module:validate <module-path>\n";
            return 1;
        }

        if (!is_dir($modulePath)) {
            echo "❌ Module directory does not exist: {$modulePath}\n";
            return 1;
        }

        echo "Validating module: {$modulePath}\n";
        echo str_repeat("=", 50) . "\n\n";

        // Validate structure
        $structureResult = $this->validator->validateModuleStructure($modulePath);
        
        // Extract and validate metadata
        $metadata = $this->validator->extractMetadata($modulePath);
        $metadataResult = null;
        
        if ($metadata !== null) {
            $metadataResult = $this->validator->validateMetadata($metadata);
        }

        // Display results
        $hasErrors = false;

        if ($structureResult->hasErrors()) {
            $hasErrors = true;
            echo "❌ Structure Errors:\n";
            foreach ($structureResult->getErrors() as $error) {
                echo "  - {$error}\n";
            }
            echo "\n";
        }

        if ($structureResult->hasWarnings()) {
            echo "⚠️  Structure Warnings:\n";
            foreach ($structureResult->getWarnings() as $warning) {
                echo "  - {$warning}\n";
            }
            echo "\n";
        }

        if ($metadataResult !== null) {
            if ($metadataResult->hasErrors()) {
                $hasErrors = true;
                echo "❌ Metadata Errors:\n";
                foreach ($metadataResult->getErrors() as $error) {
                    echo "  - {$error}\n";
                }
                echo "\n";
            }

            if ($metadataResult->hasWarnings()) {
                echo "⚠️  Metadata Warnings:\n";
                foreach ($metadataResult->getWarnings() as $warning) {
                    echo "  - {$warning}\n";
                }
                echo "\n";
            }

            // Display metadata
            echo "📋 Module Metadata:\n";
            foreach ($metadata as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
            echo "\n";
        }

        if (!$hasErrors && !$structureResult->hasWarnings() && ($metadataResult === null || !$metadataResult->hasWarnings())) {
            echo "✅ Module validation passed!\n";
            return 0;
        }

        if ($hasErrors) {
            return 1;
        }

        return 0;
    }
}
