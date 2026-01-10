<?php

declare(strict_types=1);

namespace MAAF\Core\Cli;

use MAAF\Core\Cli\Commands\EventConsumeCommand;
use MAAF\Core\Cli\Commands\HelpCommand;
use MAAF\Core\Cli\Commands\ListRoutesCommand;
use MAAF\Core\Cli\Commands\MakeModuleCommand;
use MAAF\Core\Cli\Commands\ValidateModuleCommand;
use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\ModuleGenerator\ModuleGenerator;
use MAAF\Core\ModuleValidator\ModuleValidator;
use MAAF\Core\Routing\Router;

/**
 * CLI 1.0
 * 
 * Stabil CLI rendszer.
 * 
 * @version 1.0.0
 */
final class Cli
{
    private CommandRunner $commandRunner;

    public function __construct(ContainerInterface $container)
    {
        $this->commandRunner = new CommandRunner();

        // Register built-in commands
        $this->commandRunner->register(new HelpCommand($this->commandRunner));

        if ($container->has(Router::class)) {
            $router = $container->get(Router::class);
            $this->commandRunner->register(new ListRoutesCommand($router));
        }

        // Register module generator commands
        $moduleGenerator = new ModuleGenerator();
        $moduleValidator = new ModuleValidator();
        $this->commandRunner->register(new MakeModuleCommand($moduleGenerator, $moduleValidator));
        $this->commandRunner->register(new ValidateModuleCommand($moduleValidator));

        // Register event consume command
        $this->commandRunner->register(new EventConsumeCommand($container));
    }

    /**
     * Register a custom command
     * 
     * @param CommandInterface $command Command instance
     * @return void
     */
    public function register(CommandInterface $command): void
    {
        $this->commandRunner->register($command);
    }

    /**
     * Run CLI
     * 
     * @param array<string> $argv Command line arguments
     * @return int Exit code
     */
    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? null;
        $args = array_slice($argv, 2);

        if ($commandName === null) {
            $this->commandRunner->showHelp();
            return 0;
        }

        return $this->commandRunner->run($commandName, $args);
    }

    /**
     * Get command runner
     * 
     * @return CommandRunner
     */
    public function getCommandRunner(): CommandRunner
    {
        return $this->commandRunner;
    }
}
