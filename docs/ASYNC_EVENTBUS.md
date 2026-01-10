# Async EventBus Dokumentáció

## Áttekintés

Az Async EventBus lehetővé teszi aszinkron eseménykezelést RabbitMQ vagy Redis Streams használatával, retry logikával, dead letter queue-val és modulonkénti routinggal.

## Funkciók

- ✅ **Async Publish** - Aszinkron esemény publikálás
- ✅ **Retry Logic** - Automatikus újrapróbálkozás exponenciális backoff-fal
- ✅ **Dead Letter Queue** - Sikertelen események kezelése
- ✅ **Module Routing** - Modulonkénti esemény routing

## Telepítés

```bash
composer require maaf/core
```

Szükséges függőségek:
- `php-amqplib/php-amqplib` - RabbitMQ támogatáshoz
- `predis/predis` - Redis Streams támogatáshoz

## Használat

### RabbitMQ Adapter

```php
use MAAF\Core\EventBus\AsyncEventBus;
use MAAF\Core\EventBus\Adapters\RabbitMQAdapter;
use MAAF\Core\EventBus\RetryPolicy;

// RabbitMQ adapter létrehozása
$adapter = new RabbitMQAdapter(
    host: 'localhost',
    port: 5672,
    user: 'guest',
    password: 'guest'
);

// Async EventBus létrehozása
$eventBus = new AsyncEventBus(
    queueAdapter: $adapter,
    exchangeName: 'maaf.events',
    dlqName: 'maaf.events.dlq',
    defaultRetryPolicy: RetryPolicy::default()
);

// Modul queue regisztrálása
$eventBus->registerModuleQueue('UserModule', 'maaf.events.user');

// Aszinkron esemény publikálás
$messageId = $eventBus->publishAsync('user.created', [
    'id' => 123,
    'name' => 'John Doe',
]);

// Modulhoz való publikálás
$messageId = $eventBus->publishToModule('UserModule', 'user.created', [
    'id' => 123,
]);
```

### Redis Streams Adapter

```php
use MAAF\Core\EventBus\AsyncEventBus;
use MAAF\Core\EventBus\Adapters\RedisStreamsAdapter;
use MAAF\Core\EventBus\RetryPolicy;

// Redis Streams adapter létrehozása
$adapter = new RedisStreamsAdapter(
    host: 'localhost',
    port: 6379,
    password: null,
    database: 0
);

// Async EventBus létrehozása
$eventBus = new AsyncEventBus(
    queueAdapter: $adapter,
    exchangeName: 'maaf.events',
    dlqName: 'maaf.events.dlq'
);

// Aszinkron esemény publikálás
$messageId = $eventBus->publishAsync('user.created', [
    'id' => 123,
]);
```

### Event Consuming

```php
// Események feldolgozása
$eventBus->consume(function ($payload, string $eventName, EventMessage $message) {
    echo "Processing event: {$eventName}\n";
    echo "Payload: " . json_encode($payload) . "\n";
    echo "Message ID: {$message->id}\n";
    echo "Retry count: {$message->retryCount}\n";
    
    // Business logic here
    processUserCreated($payload);
}, [
    'queue' => 'maaf.events.user',
    'consumer_group' => 'user-processors',
    'consumer_name' => 'worker-1',
]);
```

### Retry Policy

```php
use MAAF\Core\EventBus\RetryPolicy;

// Alapértelmezett retry policy
$defaultPolicy = RetryPolicy::default(); // 3 retries, exponential backoff

// Nincs retry
$noRetryPolicy = RetryPolicy::noRetry();

// Agresszív retry policy
$aggressivePolicy = RetryPolicy::aggressive(); // 10 retries

// Egyedi retry policy
$customPolicy = new RetryPolicy(
    maxRetries: 5,
    initialDelay: 2,
    backoffMultiplier: 1.5,
    maxDelay: 600,
    exponentialBackoff: true
);

// Event-specifikus retry policy
$eventBus->setRetryPolicy('user.created', $customPolicy);
```

### Dead Letter Queue

A sikertelen események automatikusan a dead letter queue-ba kerülnek, ha elérte a maximális retry számot.

```php
// DLQ feldolgozása
$eventBus->consume(function ($payload, string $eventName, EventMessage $message) {
    // Log failed events
    logFailedEvent($message);
    
    // Manual retry vagy admin notification
    notifyAdmin($message);
}, [
    'queue' => 'maaf.events.dlq',
]);
```

### Module Routing

```php
// Modul queue regisztrálása
$eventBus->registerModuleQueue('UserModule', 'maaf.events.user');
$eventBus->registerModuleQueue('OrderModule', 'maaf.events.order');

// Esemény publikálása specifikus modulhoz
$eventBus->publishToModule('UserModule', 'user.created', [
    'id' => 123,
]);

// Az esemény csak a UserModule queue-ba kerül
```

## Konfiguráció

### RabbitMQ Konfiguráció

```php
$adapter = new RabbitMQAdapter(
    host: getenv('RABBITMQ_HOST') ?: 'localhost',
    port: (int) (getenv('RABBITMQ_PORT') ?: 5672),
    user: getenv('RABBITMQ_USER') ?: 'guest',
    password: getenv('RABBITMQ_PASSWORD') ?: 'guest',
    vhost: getenv('RABBITMQ_VHOST') ?: '/'
);
```

### Redis Konfiguráció

```php
$adapter = new RedisStreamsAdapter(
    host: getenv('REDIS_HOST') ?: 'localhost',
    port: (int) (getenv('REDIS_PORT') ?: 6379),
    password: getenv('REDIS_PASSWORD'),
    database: (int) (getenv('REDIS_DATABASE') ?: 0)
);
```

## Best Practices

1. **Queue Naming**: Használj konzisztens queue neveket (pl. `maaf.events.{module}`)
2. **Retry Policy**: Állíts be megfelelő retry policy-t az esemény típusának megfelelően
3. **Error Handling**: Mindig kezeld a hibákat a consumer-ben
4. **DLQ Monitoring**: Monitorozd a dead letter queue-t
5. **Module Isolation**: Használj külön queue-kat modulonként az izoláció érdekében

## Példák

### Teljes példa RabbitMQ-val

```php
use MAAF\Core\EventBus\AsyncEventBus;
use MAAF\Core\EventBus\Adapters\RabbitMQAdapter;
use MAAF\Core\EventBus\RetryPolicy;

// Setup
$adapter = new RabbitMQAdapter('localhost', 5672, 'guest', 'guest');
$eventBus = new AsyncEventBus($adapter);

// Register module queues
$eventBus->registerModuleQueue('UserModule', 'maaf.events.user');

// Publish event
$eventBus->publishAsync('user.created', [
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com',
], [
    'module' => 'UserModule',
]);

// Consume events
$eventBus->consume(function ($payload, $eventName, $message) {
    echo "Processing: {$eventName}\n";
    // Process event
}, [
    'queue' => 'maaf.events.user',
]);
```

### Worker Script

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use MAAF\Core\EventBus\AsyncEventBus;
use MAAF\Core\EventBus\Adapters\RabbitMQAdapter;

$adapter = new RabbitMQAdapter('localhost', 5672, 'guest', 'guest');
$eventBus = new AsyncEventBus($adapter);

echo "Starting event consumer...\n";

$eventBus->consume(function ($payload, $eventName, $message) {
    echo "[{$message->id}] Processing {$eventName}\n";
    
    try {
        // Process event
        processEvent($eventName, $payload);
        echo "[{$message->id}] Success\n";
    } catch (\Exception $e) {
        echo "[{$message->id}] Error: {$e->getMessage()}\n";
        throw $e; // Will trigger retry or DLQ
    }
}, [
    'queue' => 'maaf.events.default',
]);
```

## További információk

- [EventBus Dokumentáció](README.md#3-eventbus-10)
- [Module Loader Dokumentáció](README.md#2-module-loader-30)
