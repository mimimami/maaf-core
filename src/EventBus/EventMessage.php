<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus;

/**
 * Event Message
 * 
 * Esemény üzenet osztály.
 * 
 * @version 2.0.0
 */
final class EventMessage
{
    /**
     * @param string $id Message ID
     * @param string $eventName Event name
     * @param mixed $payload Event payload
     * @param string|null $moduleName Target module name (optional)
     * @param array<string, mixed> $metadata Additional metadata
     * @param int $retryCount Current retry count
     * @param int $timestamp Message timestamp
     */
    public function __construct(
        public readonly string $id,
        public readonly string $eventName,
        public readonly mixed $payload,
        public readonly ?string $moduleName = null,
        public readonly array $metadata = [],
        public readonly int $retryCount = 0,
        public readonly int $timestamp = 0
    ) {
    }

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data Message data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? uniqid('msg_', true),
            eventName: $data['event_name'] ?? '',
            payload: $data['payload'] ?? null,
            moduleName: $data['module_name'] ?? null,
            metadata: $data['metadata'] ?? [],
            retryCount: $data['retry_count'] ?? 0,
            timestamp: $data['timestamp'] ?? time()
        );
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event_name' => $this->eventName,
            'payload' => $this->payload,
            'module_name' => $this->moduleName,
            'metadata' => $this->metadata,
            'retry_count' => $this->retryCount,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Create retry message
     * 
     * @return self
     */
    public function withRetry(): self
    {
        return new self(
            id: $this->id,
            eventName: $this->eventName,
            payload: $this->payload,
            moduleName: $this->moduleName,
            metadata: $this->metadata,
            retryCount: $this->retryCount + 1,
            timestamp: $this->timestamp
        );
    }

    /**
     * Serialize to JSON
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Deserialize from JSON
     * 
     * @param string $json JSON string
     * @return self
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }
}
