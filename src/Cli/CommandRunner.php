<?php

declare(strict_types=1);

namespace MAAF\Core\Cli;

/**
 * Command Runner
 * 
 * CLI parancsok futtatásáért felelős osztály.
 * 
 * @version 1.0.0
 */
final class CommandRunner
{
    /**
     * @var array<string, CommandInterface>
     */
    private array $commands = [];

    /**
     * Register a command
     * 
     * @param CommandInterface $command Command instance
     * @return void
     */
    public function register(CommandInterface $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Run a command
     * 
     * @param string $commandName Command name
     * @param array<string> $args Command arguments
     * @return int Exit code
     */
    public function run(string $commandName, array $args = []): int
    {
        if (!isset($this->commands[$commandName])) {
            $this->showError("Command not found: {$commandName}");
            $this->showHelp();
            return 1;
        }

        $command = $this->commands[$commandName];
        return $command->execute($args);
    }

    /**
     * Show help
     * 
     * @return void
     */
    public function showHelp(): void
    {
        echo "MAAF CLI Tool\n";
        echo "=============\n\n";
        echo "Available commands:\n\n";

        foreach ($this->commands as $command) {
            printf("  %-20s %s\n", $command->getName(), $command->getDescription());
        }

        echo "\n";
    }

    /**
     * Show error message
     * 
     * @param string $message Error message
     * @return void
     */
    private function showError(string $message): void
    {
        echo "❌ {$message}\n";
    }

    /**
     * Get all registered commands
     * 
     * @return array<string, CommandInterface>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
