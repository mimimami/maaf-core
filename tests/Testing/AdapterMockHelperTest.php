<?php

declare(strict_types=1);

namespace MAAF\Core\Tests\Testing;

use MAAF\Core\EventBus\EventMessage;
use MAAF\Core\Testing\AdapterMockHelper;
use PHPUnit\Framework\TestCase;

/**
 * Adapter Mock Helper Test
 * 
 * Példa teszt az AdapterMockHelper használatára.
 */
final class AdapterMockHelperTest extends TestCase
{
    public function testMockQueueAdapter(): void
    {
        $mockAdapter = AdapterMockHelper::createMockQueueAdapter();

        $message = new EventMessage(
            id: 'test-123',
            eventName: 'test.event',
            payload: ['data' => 'test']
        );

        $messageId = $mockAdapter->publish('test.queue', $message);

        $this->assertEquals('test-123', $messageId);
        
        $messages = $mockAdapter->getPublishedMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('test.queue', $messages[0]['queue']);
    }

    public function testMockQueueAdapterConsume(): void
    {
        $mockAdapter = AdapterMockHelper::createMockQueueAdapter();

        $received = false;
        $mockAdapter->consume('test.queue', function (EventMessage $message) use (&$received) {
            $received = true;
            $this->assertEquals('test.event', $message->eventName);
        });

        $message = new EventMessage(
            id: 'test-123',
            eventName: 'test.event',
            payload: ['data' => 'test']
        );

        $mockAdapter->simulateMessageDelivery('test.queue', $message);

        $this->assertTrue($received);
    }

    public function testMockQueueAdapterAcknowledge(): void
    {
        $mockAdapter = AdapterMockHelper::createMockQueueAdapter();

        $message = new EventMessage(
            id: 'test-123',
            eventName: 'test.event',
            payload: []
        );

        $mockAdapter->publish('test.queue', $message);
        $mockAdapter->acknowledge('test-123');

        $acknowledged = $mockAdapter->getAcknowledgedMessages();
        $this->assertArrayHasKey('test-123', $acknowledged);
    }

    public function testMockEventBusAdapter(): void
    {
        $mockAdapter = AdapterMockHelper::createMockEventBusAdapter();

        $received = false;
        $mockAdapter->subscribe('test.event', function ($payload) use (&$received) {
            $received = true;
            $this->assertEquals('test-data', $payload);
        });

        $mockAdapter->publish('test.event', 'test-data');

        $this->assertTrue($received);
        
        $events = $mockAdapter->getPublishedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('test.event', $events[0]['eventName']);
    }
}
