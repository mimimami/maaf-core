<?php

declare(strict_types=1);

namespace MAAF\Core\Config;

/**
 * Config Implementation
 * 
 * Konfigurációs motor implementáció.
 * 
 * @version 1.0.0
 */
final class Config implements ConfigInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function loadFromFile(string $filePath, ?string $prefix = null): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $config = require $filePath;

        if (!is_array($config)) {
            return;
        }

        $this->loadFromArray($config, $prefix);
    }

    public function loadFromArray(array $config, ?string $prefix = null): void
    {
        if ($prefix !== null) {
            $this->config[$prefix] = array_merge(
                $this->config[$prefix] ?? [],
                $config
            );
        } else {
            $this->config = array_merge($this->config, $config);
        }
    }
}
