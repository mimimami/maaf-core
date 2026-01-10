<?php

declare(strict_types=1);

namespace MAAF\Core\Config;

/**
 * Config Interface
 * 
 * Stabil API a konfigurációs motor számára.
 * 
 * @version 1.0.0
 */
interface ConfigInterface
{
    /**
     * Get a configuration value
     * 
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value
     * 
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $value Configuration value
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a configuration key exists
     * 
     * @param string $key Configuration key (supports dot notation)
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get all configuration
     * 
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Load configuration from file
     * 
     * @param string $filePath Path to configuration file
     * @param string|null $prefix Optional prefix for keys
     * @return void
     */
    public function loadFromFile(string $filePath, ?string $prefix = null): void;

    /**
     * Load configuration from array
     * 
     * @param array<string, mixed> $config Configuration array
     * @param string|null $prefix Optional prefix for keys
     * @return void
     */
    public function loadFromArray(array $config, ?string $prefix = null): void;
}
