<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus;

/**
 * Queue Adapter Interface
 * 
 * Interface a queue adapterek számára (RabbitMQ, Redis Streams, stb.).
 * 
 * @version 2.0.0
 */
interface QueueAdapterInterface
{
    /**
     * Publish message to queue
     * 
     * @param string $queue Queue name
     * @param EventMessage $message Event message
     * @param array<string, mixed> $options Publishing options
     * @return string|null Message ID
     */
    public function publish(string $queue, EventMessage $message, array $options = []): ?string;

    /**
     * Consume messages from queue
     * 
     * @param string $queue Queue name
     * @param callable $handler Message handler
     * @param array<string, mixed> $options Consumer options
     * @return void
     */
    public function consume(string $queue, callable $handler, array $options = []): void;

    /**
     * Acknowledge message
     * 
     * @param string $messageId Message ID
     * @return void
     */
    public function acknowledge(string $messageId): void;

    /**
     * Reject message
     * 
     * @param string $messageId Message ID
     * @param bool $requeue Whether to requeue
     * @return void
     */
    public function reject(string $messageId, bool $requeue = true): void;

    /**
     * Declare queue
     * 
     * @param string $queue Queue name
     * @param array<string, mixed> $options Queue options
     * @return void
     */
    public function declareQueue(string $queue, array $options = []): void;

    /**
     * Declare exchange
     * 
     * @param string $exchange Exchange name
     * @param string $type Exchange type (direct, topic, fanout)
     * @param array<string, mixed> $options Exchange options
     * @return void
     */
    public function declareExchange(string $exchange, string $type = 'topic', array $options = []): void;

    /**
     * Bind queue to exchange
     * 
     * @param string $queue Queue name
     * @param string $exchange Exchange name
     * @param string $routingKey Routing key
     * @return void
     */
    public function bindQueue(string $queue, string $exchange, string $routingKey = ''): void;
}
