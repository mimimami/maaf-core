<?php

declare(strict_types=1);

namespace MAAF\Core\Cli\Commands;

use MAAF\Core\Cli\CommandInterface;
use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\EventBus\AsyncEventBusInterface;
use MAAF\Core\EventBus\EventMessage;

/**
 * Event Consume Command
 * 
 * Futtat egy event consumer-t.
 * 
 * @version 2.0.0
 */
final class EventConsumeCommand implements CommandInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function getName(): string
    {
        return 'event:consume';
    }

    public function getDescription(): string
    {
        return 'Consume events from queue';
    }

    public function execute(array $args): int
    {
        $queue = $args[0] ?? null;
        $consumerGroup = $args[1] ?? 'default';
        $consumerName = $args[2] ?? uniqid('consumer_', true);

        if ($queue === null) {
            echo "❌ Queue name required\n";
            echo "Usage: php maaf event:consume <queue> [consumer-group] [consumer-name]\n";
            return 1;
        }

        if (!$this->container->has(AsyncEventBusInterface::class)) {
            echo "❌ AsyncEventBus not configured\n";
            echo "Please configure AsyncEventBus in your services.php\n";
            return 1;
        }

        /** @var AsyncEventBusInterface $eventBus */
        $eventBus = $this->container->get(AsyncEventBusInterface::class);

        echo "Starting event consumer...\n";
        echo "Queue: {$queue}\n";
        echo "Consumer Group: {$consumerGroup}\n";
        echo "Consumer Name: {$consumerName}\n";
        echo str_repeat("=", 50) . "\n\n";

        try {
            $eventBus->consume(function ($payload, string $eventName, EventMessage $message) use ($eventBus) {
                echo "[{$message->id}] Processing event: {$eventName}\n";
                echo "  Retry count: {$message->retryCount}\n";
                echo "  Module: " . ($message->moduleName ?? 'N/A') . "\n";
                echo "  Payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
                echo "\n";
                
                // Call registered listeners
                if ($eventBus instanceof \MAAF\Core\EventBus\EventBusInterface) {
                    $listeners = $eventBus->getListeners($eventName);
                    foreach ($listeners as $listener) {
                        $listener($payload, $eventName);
                    }
                }
            }, [
                'queue' => $queue,
                'consumer_group' => $consumerGroup,
                'consumer_name' => $consumerName,
            ]);

            return 0;
        } catch (\Throwable $e) {
            echo "❌ Error: {$e->getMessage()}\n";
            echo "Trace: {$e->getTraceAsString()}\n";
            return 1;
        }
    }
}
