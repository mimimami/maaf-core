<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus;

/**
 * Async EventBus Implementation
 * 
 * Aszinkron eseménykezelő rendszer implementáció.
 * 
 * @version 2.0.0
 */
final class AsyncEventBus implements AsyncEventBusInterface
{
    /**
     * @var array<string, array<int, array{listener: callable, priority: int}>>
     */
    private array $listeners = [];

    /**
     * @var array<string, RetryPolicy>
     */
    private array $retryPolicies = [];

    /**
     * @var array<string, string>
     */
    private array $moduleQueues = [];

    public function __construct(
        private readonly QueueAdapterInterface $queueAdapter,
        private readonly string $exchangeName = 'maaf.events',
        private readonly string $dlqName = 'maaf.events.dlq',
        private readonly RetryPolicy $defaultRetryPolicy = new RetryPolicy()
    ) {
        $this->initialize();
    }

    /**
     * Initialize queues and exchanges
     * 
     * @return void
     */
    private function initialize(): void
    {
        // Declare main exchange
        $this->queueAdapter->declareExchange($this->exchangeName, 'topic', [
            'durable' => true,
        ]);

        // Declare dead letter queue
        $this->queueAdapter->declareQueue($this->dlqName, [
            'durable' => true,
        ]);

        // Bind DLQ to exchange
        $this->queueAdapter->bindQueue($this->dlqName, $this->exchangeName, 'dlq');
    }

    public function subscribe(string $eventName, callable $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        usort($this->listeners[$eventName], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    public function unsubscribe(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        $this->listeners[$eventName] = array_filter(
            $this->listeners[$eventName],
            function ($item) use ($listener) {
                return $item['listener'] !== $listener;
            }
        );

        if (empty($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }
    }

    public function publish(string $eventName, mixed $payload = null): void
    {
        // Synchronous publish (immediate execution)
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $item) {
            $listener = $item['listener'];
            $listener($payload, $eventName);
        }
    }

    public function publishAsync(string $eventName, mixed $payload = null, array $options = []): ?string
    {
        $message = new EventMessage(
            id: uniqid('evt_', true),
            eventName: $eventName,
            payload: $payload,
            moduleName: $options['module'] ?? null,
            metadata: $options['metadata'] ?? [],
            retryCount: 0,
            timestamp: time()
        );

        $queue = $this->getQueueForEvent($eventName, $options['module'] ?? null);
        
        return $this->queueAdapter->publish($queue, $message, $options);
    }

    public function publishToModule(string $moduleName, string $eventName, mixed $payload = null, array $options = []): ?string
    {
        $options['module'] = $moduleName;
        return $this->publishAsync($eventName, $payload, $options);
    }

    public function consume(callable $handler, array $options = []): void
    {
        $queue = $options['queue'] ?? $this->getDefaultQueue();
        $retryPolicy = $options['retry_policy'] ?? $this->defaultRetryPolicy;

        $this->queueAdapter->consume($queue, function (EventMessage $message) use ($handler, $retryPolicy) {
            try {
                // Execute handler
                $handler($message->payload, $message->eventName, $message);

                // Acknowledge on success
                $this->queueAdapter->acknowledge($message->id);
            } catch (\Throwable $e) {
                // Handle retry logic
                if ($retryPolicy->shouldRetry($message->retryCount)) {
                    $this->handleRetry($message, $retryPolicy);
                } else {
                    // Send to dead letter queue
                    $this->sendToDLQ($message, $e);
                }
            }
        }, $options);
    }

    public function acknowledge(string $messageId): void
    {
        $this->queueAdapter->acknowledge($messageId);
    }

    public function reject(string $messageId, bool $requeue = true): void
    {
        $this->queueAdapter->reject($messageId, $requeue);
    }

    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) && !empty($this->listeners[$eventName]);
    }

    public function getListeners(string $eventName): array
    {
        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        return array_map(
            fn($item) => $item['listener'],
            $this->listeners[$eventName]
        );
    }

    /**
     * Set retry policy for event
     * 
     * @param string $eventName Event name
     * @param RetryPolicy $policy Retry policy
     * @return void
     */
    public function setRetryPolicy(string $eventName, RetryPolicy $policy): void
    {
        $this->retryPolicies[$eventName] = $policy;
    }

    /**
     * Register module queue
     * 
     * @param string $moduleName Module name
     * @param string $queueName Queue name
     * @return void
     */
    public function registerModuleQueue(string $moduleName, string $queueName): void
    {
        $this->moduleQueues[$moduleName] = $queueName;
        
        // Declare and bind module queue
        $this->queueAdapter->declareQueue($queueName, [
            'durable' => true,
            'arguments' => [
                'x-dead-letter-exchange' => $this->exchangeName,
                'x-dead-letter-routing-key' => 'dlq',
            ],
        ]);
        
        $this->queueAdapter->bindQueue($queueName, $this->exchangeName, "module.{$moduleName}.*");
    }

    /**
     * Get queue for event
     * 
     * @param string $eventName Event name
     * @param string|null $moduleName Module name
     * @return string
     */
    private function getQueueForEvent(string $eventName, ?string $moduleName = null): string
    {
        if ($moduleName !== null && isset($this->moduleQueues[$moduleName])) {
            return $this->moduleQueues[$moduleName];
        }

        return $this->getDefaultQueue();
    }

    /**
     * Get default queue
     * 
     * @return string
     */
    private function getDefaultQueue(): string
    {
        return 'maaf.events.default';
    }

    /**
     * Handle retry
     * 
     * @param EventMessage $message Original message
     * @param RetryPolicy $policy Retry policy
     * @return void
     */
    private function handleRetry(EventMessage $message, RetryPolicy $policy): void
    {
        $retryMessage = $message->withRetry();
        $delay = $policy->getDelay($retryMessage->retryCount);

        // Publish to retry queue with delay
        $retryQueue = "maaf.events.retry.{$delay}";
        $this->queueAdapter->declareQueue($retryQueue, [
            'durable' => true,
            'arguments' => [
                'x-message-ttl' => $delay * 1000, // Convert to milliseconds
                'x-dead-letter-exchange' => $this->exchangeName,
                'x-dead-letter-routing-key' => $message->eventName,
            ],
        ]);

        $this->queueAdapter->publish($retryQueue, $retryMessage);
    }

    /**
     * Send message to dead letter queue
     * 
     * @param EventMessage $message Message
     * @param \Throwable $error Error that occurred
     * @return void
     */
    private function sendToDLQ(EventMessage $message, \Throwable $error): void
    {
        $dlqMessage = new EventMessage(
            id: $message->id,
            eventName: $message->eventName,
            payload: $message->payload,
            moduleName: $message->moduleName,
            metadata: array_merge($message->metadata, [
                'error' => $error->getMessage(),
                'error_class' => get_class($error),
                'failed_at' => time(),
            ]),
            retryCount: $message->retryCount,
            timestamp: $message->timestamp
        );

        $this->queueAdapter->publish($this->dlqName, $dlqMessage);
    }
}
