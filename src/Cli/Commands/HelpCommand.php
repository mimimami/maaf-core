<?php

declare(strict_types=1);

namespace MAAF\Core\Cli\Commands;

use MAAF\Core\Cli\CommandInterface;
use MAAF\Core\Cli\CommandRunner;

/**
 * Help Command
 * 
 * Shows help information for CLI commands.
 * 
 * @version 1.0.0
 */
final class HelpCommand implements CommandInterface
{
    public function __construct(
        private readonly CommandRunner $commandRunner
    ) {
    }

    public function getName(): string
    {
        return 'help';
    }

    public function getDescription(): string
    {
        return 'Show help information';
    }

    public function execute(array $args): int
    {
        $this->commandRunner->showHelp();
        return 0;
    }
}
