<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus;

/**
 * EventBus Implementation
 * 
 * Eseménykezelő rendszer implementáció.
 * 
 * @version 1.0.0
 */
final class EventBus implements EventBusInterface
{
    /**
     * @var array<string, array<int, array{listener: callable, priority: int}>>
     */
    private array $listeners = [];

    public function subscribe(string $eventName, callable $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        // Sort by priority (higher priority first)
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
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $item) {
            $listener = $item['listener'];
            $listener($payload, $eventName);
        }
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
}
