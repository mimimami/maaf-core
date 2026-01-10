<?php

declare(strict_types=1);

namespace MAAF\Core\Cli;

/**
 * Command Interface
 * 
 * Interface for CLI commands.
 * 
 * @version 1.0.0
 */
interface CommandInterface
{
    /**
     * Get command name
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Get command description
     * 
     * @return string
     */
    public function getDescription(): string;

    /**
     * Execute the command
     * 
     * @param array<string> $args Command arguments
     * @return int Exit code (0 = success, non-zero = error)
     */
    public function execute(array $args): int;
}
