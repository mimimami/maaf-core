<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus\Adapters;

use MAAF\Core\EventBus\EventMessage;
use MAAF\Core\EventBus\QueueAdapterInterface;
use Predis\Client as PredisClient;

/**
 * Redis Streams Queue Adapter
 * 
 * Redis Streams adapter a queue műveletekhez.
 * 
 * @version 2.0.0
 */
final class RedisStreamsAdapter implements QueueAdapterInterface
{
    private PredisClient $redis;

    /**
     * @var array<string, string>
     */
    private array $pendingMessages = [];

    public function __construct(
        string $host = 'localhost',
        int $port = 6379,
        ?string $password = null,
        int $database = 0,
        ?PredisClient $redis = null
    ) {
        if ($redis !== null) {
            $this->redis = $redis;
        } else {
            $config = [
                'host' => $host,
                'port' => $port,
                'database' => $database,
            ];
            
            if ($password !== null) {
                $config['password'] = $password;
            }
            
            $this->redis = new PredisClient($config);
        }
    }

    public function publish(string $queue, EventMessage $message, array $options = []): ?string
    {
        $streamKey = $this->getStreamKey($queue);
        
        $data = [
            'id' => $message->id,
            'event_name' => $message->eventName,
            'payload' => json_encode($message->payload, JSON_THROW_ON_ERROR),
            'module_name' => $message->moduleName ?? '',
            'retry_count' => (string) $message->retryCount,
            'timestamp' => (string) $message->timestamp,
            'metadata' => json_encode($message->metadata, JSON_THROW_ON_ERROR),
        ];

        $messageId = $this->redis->xadd($streamKey, '*', $data);

        return $messageId !== null ? $message->id : null;
    }

    public function consume(string $queue, callable $handler, array $options = []): void
    {
        $streamKey = $this->getStreamKey($queue);
        $consumerGroup = $options['consumer_group'] ?? 'default';
        $consumerName = $options['consumer_name'] ?? uniqid('consumer_', true);
        $block = $options['block'] ?? 1000; // Block for 1 second
        $count = $options['count'] ?? 10;

        // Create consumer group if it doesn't exist
        $this->createConsumerGroup($streamKey, $consumerGroup);

        while (true) {
            // Read new messages
            $messages = $this->redis->xreadgroup(
                $consumerGroup,
                $consumerName,
                [$streamKey => '>'],
                $count,
                $block * 1000 // Convert to milliseconds
            );

            if (empty($messages)) {
                continue;
            }

            foreach ($messages[$streamKey] ?? [] as $messageId => $data) {
                try {
                    $message = $this->parseMessage($data);
                    $this->pendingMessages[$message->id] = $messageId;

                    $handler($message);

                    // Acknowledge
                    $this->redis->xack($streamKey, $consumerGroup, [$messageId]);
                    unset($this->pendingMessages[$message->id]);
                } catch (\Throwable $e) {
                    // Handle error (will be retried or sent to DLQ)
                    throw $e;
                }
            }

            // Process pending messages (retries)
            $this->processPendingMessages($streamKey, $consumerGroup, $consumerName, $handler);
        }
    }

    public function acknowledge(string $messageId): void
    {
        // Acknowledgment is handled in consume method
        if (isset($this->pendingMessages[$messageId])) {
            unset($this->pendingMessages[$messageId]);
        }
    }

    public function reject(string $messageId, bool $requeue = true): void
    {
        // In Redis Streams, rejection means not acknowledging
        // If requeue is true, the message will be redelivered
        if (isset($this->pendingMessages[$messageId])) {
            unset($this->pendingMessages[$messageId]);
        }
    }

    public function declareQueue(string $queue, array $options = []): void
    {
        $streamKey = $this->getStreamKey($queue);
        
        // Create stream if it doesn't exist (Redis Streams auto-creates)
        // But we can set max length if specified
        if (isset($options['max_length'])) {
            $this->redis->xtrim($streamKey, $options['max_length'], true);
        }
    }

    public function declareExchange(string $exchange, string $type = 'topic', array $options = []): void
    {
        // Redis Streams doesn't have exchanges, but we can create a stream for routing
        $streamKey = $this->getStreamKey($exchange);
        $this->declareQueue($exchange, $options);
    }

    public function bindQueue(string $queue, string $exchange, string $routingKey = ''): void
    {
        // Redis Streams doesn't have bindings, but we can use routing keys in message metadata
        // This is a no-op for Redis Streams
    }

    /**
     * Get stream key
     * 
     * @param string $queue Queue name
     * @return string
     */
    private function getStreamKey(string $queue): string
    {
        return "maaf:stream:{$queue}";
    }

    /**
     * Create consumer group
     * 
     * @param string $streamKey Stream key
     * @param string $groupName Group name
     * @return void
     */
    private function createConsumerGroup(string $streamKey, string $groupName): void
    {
        try {
            $this->redis->xgroup('CREATE', $streamKey, $groupName, '0', true);
        } catch (\Exception $e) {
            // Group already exists, ignore
            if (!str_contains($e->getMessage(), 'BUSYGROUP')) {
                throw $e;
            }
        }
    }

    /**
     * Parse message from Redis Streams data
     * 
     * @param array<string, string> $data Message data
     * @return EventMessage
     */
    private function parseMessage(array $data): EventMessage
    {
        return new EventMessage(
            id: $data['id'] ?? uniqid('msg_', true),
            eventName: $data['event_name'] ?? '',
            payload: json_decode($data['payload'] ?? 'null', true),
            moduleName: !empty($data['module_name']) ? $data['module_name'] : null,
            metadata: json_decode($data['metadata'] ?? '{}', true),
            retryCount: (int) ($data['retry_count'] ?? 0),
            timestamp: (int) ($data['timestamp'] ?? time())
        );
    }

    /**
     * Process pending messages
     * 
     * @param string $streamKey Stream key
     * @param string $consumerGroup Consumer group
     * @param string $consumerName Consumer name
     * @param callable $handler Message handler
     * @return void
     */
    private function processPendingMessages(
        string $streamKey,
        string $consumerGroup,
        string $consumerName,
        callable $handler
    ): void {
        $pending = $this->redis->xpending($streamKey, $consumerGroup, '-', '+', 10, $consumerName);

        if (empty($pending)) {
            return;
        }

        $messageIds = array_column($pending, 0);
        $messages = $this->redis->xclaim(
            $streamKey,
            $consumerGroup,
            $consumerName,
            60000, // 60 seconds
            $messageIds
        );

        foreach ($messages ?? [] as $messageId => $data) {
            try {
                $message = $this->parseMessage($data);
                $this->pendingMessages[$message->id] = $messageId;

                $handler($message);

                $this->redis->xack($streamKey, $consumerGroup, [$messageId]);
                unset($this->pendingMessages[$message->id]);
            } catch (\Throwable $e) {
                // Will be retried or sent to DLQ
            }
        }
    }
}
