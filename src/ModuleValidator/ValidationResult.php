<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleValidator;

/**
 * Validation Result
 * 
 * Validálás eredményét tartalmazza.
 * 
 * @version 1.0.0
 */
final class ValidationResult
{
    /**
     * @param bool $isValid Is validation successful
     * @param array<int, string> $errors List of errors
     * @param array<int, string> $warnings List of warnings
     */
    public function __construct(
        private readonly bool $isValid,
        private readonly array $errors = [],
        private readonly array $warnings = []
    ) {
    }

    /**
     * Check if validation is successful
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get errors
     * 
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get warnings
     * 
     * @return array<int, string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get all messages (errors + warnings)
     * 
     * @return array<int, string>
     */
    public function getAllMessages(): array
    {
        return array_merge($this->errors, $this->warnings);
    }

    /**
     * Check if there are any errors
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings
     * 
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}
