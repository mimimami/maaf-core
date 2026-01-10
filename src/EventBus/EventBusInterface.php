<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus;

/**
 * EventBus Interface
 * 
 * Stabil API az eseménykezelő rendszer számára.
 * 
 * @version 1.0.0
 */
interface EventBusInterface
{
    /**
     * Subscribe to an event
     * 
     * @param string $eventName Event name
     * @param callable $listener Event listener callback
     * @param int $priority Listener priority (higher = executed first)
     * @return void
     */
    public function subscribe(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Unsubscribe from an event
     * 
     * @param string $eventName Event name
     * @param callable $listener Event listener callback
     * @return void
     */
    public function unsubscribe(string $eventName, callable $listener): void;

    /**
     * Publish an event
     * 
     * @param string $eventName Event name
     * @param mixed $payload Event payload
     * @return void
     */
    public function publish(string $eventName, mixed $payload = null): void;

    /**
     * Check if there are listeners for an event
     * 
     * @param string $eventName Event name
     * @return bool
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Get all listeners for an event
     * 
     * @param string $eventName Event name
     * @return array<callable>
     */
    public function getListeners(string $eventName): array;
}
