<?php

declare(strict_types=1);

namespace MAAF\Core\Testing;

use MAAF\Core\EventBus\EventMessage;
use MAAF\Core\EventBus\QueueAdapterInterface;

/**
 * Adapter Mock Helper
 * 
 * Segédeszközök adapterek mockolásához.
 * 
 * @version 1.0.0
 */
final class AdapterMockHelper
{
    /**
     * Create mock queue adapter
     * 
     * @return MockQueueAdapter
     */
    public static function createMockQueueAdapter(): MockQueueAdapter
    {
        return new MockQueueAdapter();
    }

    /**
     * Create mock event bus adapter
     * 
     * @return MockEventBusAdapter
     */
    public static function createMockEventBusAdapter(): MockEventBusAdapter
    {
        return new MockEventBusAdapter();
    }
}

/**
 * Mock Queue Adapter
 * 
 * Mock queue adapter teszteléshez.
 */
final class MockQueueAdapter implements QueueAdapterInterface
{
    /**
     * @var array<int, array{queue: string, message: EventMessage, options: array}>
     */
    private array $publishedMessages = [];

    /**
     * @var array<string, array<int, callable>>
     */
    private array $consumers = [];

    /**
     * @var array<string, string>
     */
    private array $acknowledgedMessages = [];

    /**
     * @var array<string, array{requeue: bool}>
     */
    private array $rejectedMessages = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $declaredQueues = [];

    /**
     * @var array<string, array{type: string, options: array}>
     */
    private array $declaredExchanges = [];

    /**
     * @var array<string, array{exchange: string, routingKey: string}>
     */
    private array $queueBindings = [];

    public function publish(string $queue, EventMessage $message, array $options = []): ?string
    {
        $this->publishedMessages[] = [
            'queue' => $queue,
            'message' => $message,
            'options' => $options,
        ];

        // Trigger consumers if any
        if (isset($this->consumers[$queue])) {
            foreach ($this->consumers[$queue] as $handler) {
                try {
                    $handler($message);
                } catch (\Throwable $e) {
                    // Ignore errors in mock
                }
            }
        }

        return $message->id;
    }

    public function consume(string $queue, callable $handler, array $options = []): void
    {
        if (!isset($this->consumers[$queue])) {
            $this->consumers[$queue] = [];
        }

        $this->consumers[$queue][] = $handler;
    }

    public function acknowledge(string $messageId): void
    {
        $this->acknowledgedMessages[$messageId] = $messageId;
    }

    public function reject(string $messageId, bool $requeue = true): void
    {
        $this->rejectedMessages[$messageId] = ['requeue' => $requeue];
    }

    public function declareQueue(string $queue, array $options = []): void
    {
        $this->declaredQueues[$queue] = $options;
    }

    public function declareExchange(string $exchange, string $type = 'topic', array $options = []): void
    {
        $this->declaredExchanges[$exchange] = [
            'type' => $type,
            'options' => $options,
        ];
    }

    public function bindQueue(string $queue, string $exchange, string $routingKey = ''): void
    {
        $this->queueBindings[$queue] = [
            'exchange' => $exchange,
            'routingKey' => $routingKey,
        ];
    }

    /**
     * Get published messages
     * 
     * @return array<int, array{queue: string, message: EventMessage, options: array}>
     */
    public function getPublishedMessages(): array
    {
        return $this->publishedMessages;
    }

    /**
     * Get messages published to specific queue
     * 
     * @param string $queue Queue name
     * @return array<int, EventMessage>
     */
    public function getMessagesForQueue(string $queue): array
    {
        $messages = [];
        
        foreach ($this->publishedMessages as $item) {
            if ($item['queue'] === $queue) {
                $messages[] = $item['message'];
            }
        }

        return $messages;
    }

    /**
     * Get acknowledged messages
     * 
     * @return array<string, string>
     */
    public function getAcknowledgedMessages(): array
    {
        return $this->acknowledgedMessages;
    }

    /**
     * Get rejected messages
     * 
     * @return array<string, array{requeue: bool}>
     */
    public function getRejectedMessages(): array
    {
        return $this->rejectedMessages;
    }

    /**
     * Get declared queues
     * 
     * @return array<string, array<string, mixed>>
     */
    public function getDeclaredQueues(): array
    {
        return $this->declaredQueues;
    }

    /**
     * Get declared exchanges
     * 
     * @return array<string, array{type: string, options: array}>
     */
    public function getDeclaredExchanges(): array
    {
        return $this->declaredExchanges;
    }

    /**
     * Get queue bindings
     * 
     * @return array<string, array{exchange: string, routingKey: string}>
     */
    public function getQueueBindings(): array
    {
        return $this->queueBindings;
    }

    /**
     * Clear all recorded data
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->publishedMessages = [];
        $this->consumers = [];
        $this->acknowledgedMessages = [];
        $this->rejectedMessages = [];
        $this->declaredQueues = [];
        $this->declaredExchanges = [];
        $this->queueBindings = [];
    }

    /**
     * Simulate message delivery
     * 
     * @param string $queue Queue name
     * @param EventMessage $message Message to deliver
     * @return void
     */
    public function simulateMessageDelivery(string $queue, EventMessage $message): void
    {
        if (isset($this->consumers[$queue])) {
            foreach ($this->consumers[$queue] as $handler) {
                $handler($message);
            }
        }
    }
}

/**
 * Mock Event Bus Adapter
 * 
 * Mock event bus adapter teszteléshez.
 */
final class MockEventBusAdapter
{
    /**
     * @var array<string, array<int, callable>>
     */
    private array $listeners = [];

    /**
     * @var array<int, array{eventName: string, payload: mixed}>
     */
    private array $publishedEvents = [];

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

    public function publish(string $eventName, mixed $payload = null): void
    {
        $this->publishedEvents[] = [
            'eventName' => $eventName,
            'payload' => $payload,
        ];

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $item) {
                $listener = $item['listener'];
                $listener($payload, $eventName);
            }
        }
    }

    /**
     * Get published events
     * 
     * @return array<int, array{eventName: string, payload: mixed}>
     */
    public function getPublishedEvents(): array
    {
        return $this->publishedEvents;
    }

    /**
     * Get events by name
     * 
     * @param string $eventName Event name
     * @return array<int, mixed>
     */
    public function getEventsByName(string $eventName): array
    {
        $events = [];
        
        foreach ($this->publishedEvents as $event) {
            if ($event['eventName'] === $eventName) {
                $events[] = $event['payload'];
            }
        }

        return $events;
    }

    /**
     * Clear all recorded data
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->listeners = [];
        $this->publishedEvents = [];
    }
}
