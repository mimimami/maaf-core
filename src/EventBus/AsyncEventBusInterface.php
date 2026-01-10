<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus;

/**
 * Async EventBus Interface
 * 
 * Aszinkron eseménykezelő rendszer interface-je.
 * 
 * @version 2.0.0
 */
interface AsyncEventBusInterface extends EventBusInterface
{
    /**
     * Publish an event asynchronously
     * 
     * @param string $eventName Event name
     * @param mixed $payload Event payload
     * @param array<string, mixed> $options Publishing options
     * @return string|null Message ID or null if failed
     */
    public function publishAsync(string $eventName, mixed $payload = null, array $options = []): ?string;

    /**
     * Publish an event to a specific module
     * 
     * @param string $moduleName Module name
     * @param string $eventName Event name
     * @param mixed $payload Event payload
     * @param array<string, mixed> $options Publishing options
     * @return string|null Message ID or null if failed
     */
    public function publishToModule(string $moduleName, string $eventName, mixed $payload = null, array $options = []): ?string;

    /**
     * Consume events from queue
     * 
     * @param callable $handler Event handler callback
     * @param array<string, mixed> $options Consumer options
     * @return void
     */
    public function consume(callable $handler, array $options = []): void;

    /**
     * Acknowledge message processing
     * 
     * @param string $messageId Message ID
     * @return void
     */
    public function acknowledge(string $messageId): void;

    /**
     * Reject message (send to retry or DLQ)
     * 
     * @param string $messageId Message ID
     * @param bool $requeue Whether to requeue for retry
     * @return void
     */
    public function reject(string $messageId, bool $requeue = true): void;
}
