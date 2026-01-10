<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus\Adapters;

use MAAF\Core\EventBus\EventMessage;
use MAAF\Core\EventBus\QueueAdapterInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQ Queue Adapter
 * 
 * RabbitMQ adapter a queue műveletekhez.
 * 
 * @version 2.0.0
 */
final class RabbitMQAdapter implements QueueAdapterInterface
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    /**
     * @var array<string, string>
     */
    private array $messageIds = [];

    public function __construct(
        string $host = 'localhost',
        int $port = 5672,
        string $user = 'guest',
        string $password = 'guest',
        string $vhost = '/'
    ) {
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channel = $this->connection->channel();
    }

    public function publish(string $queue, EventMessage $message, array $options = []): ?string
    {
        $this->declareQueue($queue, $options['queue_options'] ?? []);

        $amqpMessage = new AMQPMessage(
            $message->toJson(),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $message->id,
                'timestamp' => $message->timestamp,
                'headers' => array_merge([
                    'event_name' => $message->eventName,
                    'module_name' => $message->moduleName,
                    'retry_count' => $message->retryCount,
                ], $message->metadata),
            ]
        );

        $routingKey = $options['routing_key'] ?? $queue;
        $exchange = $options['exchange'] ?? '';

        $this->channel->basic_publish($amqpMessage, $exchange, $routingKey);

        $this->messageIds[$message->id] = $amqpMessage->getDeliveryTag();

        return $message->id;
    }

    public function consume(string $queue, callable $handler, array $options = []): void
    {
        $this->declareQueue($queue, $options['queue_options'] ?? []);

        $consumerTag = $options['consumer_tag'] ?? '';
        $noLocal = $options['no_local'] ?? false;
        $noAck = $options['no_ack'] ?? false;
        $exclusive = $options['exclusive'] ?? false;
        $nowait = $options['nowait'] ?? false;

        $callback = function (AMQPMessage $msg) use ($handler, $noAck) {
            try {
                $message = EventMessage::fromJson($msg->getBody());
                $this->messageIds[$message->id] = $msg->getDeliveryTag();

                $handler($message);

                if (!$noAck) {
                    $msg->ack();
                }
            } catch (\Throwable $e) {
                if (!$noAck) {
                    $msg->nack(false, true); // Requeue on error
                }
                throw $e;
            }
        };

        $this->channel->basic_consume(
            $queue,
            $consumerTag,
            $noLocal,
            $noAck,
            $exclusive,
            $nowait,
            $callback
        );

        // Start consuming
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function acknowledge(string $messageId): void
    {
        if (isset($this->messageIds[$messageId])) {
            $this->channel->basic_ack($this->messageIds[$messageId]);
            unset($this->messageIds[$messageId]);
        }
    }

    public function reject(string $messageId, bool $requeue = true): void
    {
        if (isset($this->messageIds[$messageId])) {
            $this->channel->basic_nack($this->messageIds[$messageId], false, $requeue);
            unset($this->messageIds[$messageId]);
        }
    }

    public function declareQueue(string $queue, array $options = []): void
    {
        $passive = $options['passive'] ?? false;
        $durable = $options['durable'] ?? true;
        $exclusive = $options['exclusive'] ?? false;
        $autoDelete = $options['auto_delete'] ?? false;
        $nowait = $options['nowait'] ?? false;
        $arguments = $options['arguments'] ?? [];

        $this->channel->queue_declare(
            $queue,
            $passive,
            $durable,
            $exclusive,
            $autoDelete,
            $nowait,
            $arguments
        );
    }

    public function declareExchange(string $exchange, string $type = 'topic', array $options = []): void
    {
        $passive = $options['passive'] ?? false;
        $durable = $options['durable'] ?? true;
        $autoDelete = $options['auto_delete'] ?? false;
        $internal = $options['internal'] ?? false;
        $nowait = $options['nowait'] ?? false;
        $arguments = $options['arguments'] ?? [];

        $this->channel->exchange_declare(
            $exchange,
            $type,
            $passive,
            $durable,
            $autoDelete,
            $internal,
            $nowait,
            $arguments
        );
    }

    public function bindQueue(string $queue, string $exchange, string $routingKey = ''): void
    {
        $this->channel->queue_bind($queue, $exchange, $routingKey);
    }

    /**
     * Close connection
     * 
     * @return void
     */
    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function __destruct()
    {
        if (isset($this->channel) && $this->channel->is_open()) {
            $this->channel->close();
        }
        if (isset($this->connection) && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }
}
