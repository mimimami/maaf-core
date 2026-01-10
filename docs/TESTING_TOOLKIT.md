# Testing Toolkit Dokumentáció

## Áttekintés

A MAAF Core Testing Toolkit segédeszközöket biztosít a modulok, use case-ek és adapterek teszteléséhez.

## Komponensek

### 1. Module Test Helper

Modulok teszteléséhez segédeszközök.

**Használat:**

```php
use MAAF\Core\Testing\ModuleTestHelper;
use PHPUnit\Framework\TestCase;

class MyModuleTest extends TestCase
{
    private ModuleTestHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new ModuleTestHelper();
    }

    public function testModuleLoads(): void
    {
        $this->helper->loadModule(
            \App\Modules\UserModule\Module::class,
            'UserModule'
        );

        $this->helper->assertModuleLoaded('UserModule');
    }

    public function testModuleRegistersRoutes(): void
    {
        $this->helper->loadModule(
            \App\Modules\UserModule\Module::class,
            'UserModule'
        );

        $this->helper->assertRouteExists('GET', '/users');
        $this->helper->assertRouteExists('POST', '/users');
    }

    public function testModuleServices(): void
    {
        $this->helper->registerServices([
            UserRepository::class => DI\create(MockUserRepository::class),
        ]);

        $this->helper->loadModule(
            \App\Modules\UserModule\Module::class,
            'UserModule'
        );

        $container = $this->helper->getContainer();
        $this->assertTrue($container->has(UserRepository::class));
    }
}
```

### 2. Use Case Test Helper

Use case-ek teszteléséhez segédeszközök.

**Használat:**

```php
use MAAF\Core\Testing\UseCaseTestHelper;
use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    private UseCaseTestHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $container = Container::fromDefinitions([
            UserService::class => DI\create(UserService::class),
        ]);
        $this->helper = new UseCaseTestHelper($container);
    }

    public function testCreateUser(): void
    {
        $request = $this->helper->createJsonRequest('POST', '/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->helper->executeController(
            [UserController::class, 'create'],
            $request
        );

        $this->helper->assertStatusCode($response, 201, $this);
        $this->helper->assertResponseContains($response, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], $this);
    }

    public function testGetUser(): void
    {
        $request = $this->helper->createRequest('GET', '/users/123');

        $response = $this->helper->executeController(
            [UserController::class, 'show'],
            $request,
            ['id' => '123']
        );

        $data = $this->helper->assertJsonResponse($response, $this);
        $this->assertEquals('123', $data['id']);
    }

    public function testServiceMethod(): void
    {
        $result = $this->helper->executeService(
            UserService::class,
            'getUserById',
            [123]
        );

        $this->assertNotNull($result);
    }
}
```

### 3. Adapter Mock Helper

Adapterek mockolásához segédeszközök.

**Használat:**

```php
use MAAF\Core\Testing\AdapterMockHelper;
use MAAF\Core\EventBus\AsyncEventBus;
use PHPUnit\Framework\TestCase;

class EventBusTest extends TestCase
{
    public function testPublishEvent(): void
    {
        $mockAdapter = AdapterMockHelper::createMockQueueAdapter();
        $eventBus = new AsyncEventBus($mockAdapter);

        $eventBus->publishAsync('user.created', [
            'id' => 123,
            'name' => 'John Doe',
        ]);

        $messages = $mockAdapter->getPublishedMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('user.created', $messages[0]['message']->eventName);
    }

    public function testConsumeEvent(): void
    {
        $mockAdapter = AdapterMockHelper::createMockQueueAdapter();
        $eventBus = new AsyncEventBus($mockAdapter);

        $received = false;
        $eventBus->consume(function ($payload, $eventName) use (&$received) {
            $received = true;
            $this->assertEquals('user.created', $eventName);
        }, ['queue' => 'test.queue']);

        // Simulate message delivery
        $message = new EventMessage(
            id: 'test-123',
            eventName: 'user.created',
            payload: ['id' => 123]
        );
        $mockAdapter->simulateMessageDelivery('test.queue', $message);

        $this->assertTrue($received);
    }

    public function testAcknowledgeMessage(): void
    {
        $mockAdapter = AdapterMockHelper::createMockQueueAdapter();
        $eventBus = new AsyncEventBus($mockAdapter);

        $messageId = $eventBus->publishAsync('test.event', []);
        $eventBus->acknowledge($messageId);

        $acknowledged = $mockAdapter->getAcknowledgedMessages();
        $this->assertArrayHasKey($messageId, $acknowledged);
    }
}
```

## Base Test Case

Használhatod a `MAAF\Core\Testing\TestCase` osztályt, amely tartalmazza az összes helper-t:

```php
use MAAF\Core\Testing\TestCase;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        // $this->moduleHelper is available
        // $this->useCaseHelper is available
        // $this->adapterHelper is available
        // $this->container is available
    }
}
```

## Példák

### Modul Tesztelés

```php
use MAAF\Core\Testing\TestCase;

class UserModuleTest extends TestCase
{
    public function testModuleLoadsAndRegistersRoutes(): void
    {
        $this->moduleHelper->loadModule(
            \App\Modules\UserModule\Module::class,
            'UserModule'
        );

        $this->moduleHelper->assertModuleLoaded('UserModule');
        $this->moduleHelper->assertRouteExists('GET', '/users');
        $this->moduleHelper->assertRouteExists('POST', '/users');
    }
}
```

### Controller Tesztelés

```php
use MAAF\Core\Testing\TestCase;

class UserControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container->set(
            UserService::class,
            $this->createMock(UserService::class)
        );
    }

    public function testIndexReturnsUsers(): void
    {
        $request = $this->useCaseHelper->createRequest('GET', '/users');
        
        $response = $this->useCaseHelper->executeController(
            [UserController::class, 'index'],
            $request
        );

        $data = $this->useCaseHelper->assertJsonResponse($response, $this);
        $this->assertArrayHasKey('data', $data);
    }
}
```

### Event Bus Tesztelés

```php
use MAAF\Core\Testing\TestCase;
use MAAF\Core\EventBus\AsyncEventBus;

class EventBusTest extends TestCase
{
    public function testAsyncPublish(): void
    {
        $mockAdapter = $this->adapterHelper->createMockQueueAdapter();
        $eventBus = new AsyncEventBus($mockAdapter);

        $eventBus->publishAsync('user.created', ['id' => 123]);

        $messages = $mockAdapter->getMessagesForQueue('maaf.events.default');
        $this->assertCount(1, $messages);
    }
}
```

## Best Practices

1. **Isolation**: Minden teszt legyen izolált
2. **Mocking**: Használj mock-okat külső függőségekhez
3. **Setup/Teardown**: Tisztítsd fel az állapotot minden teszt után
4. **Assertions**: Használj specifikus assertion-öket
5. **Naming**: Nevezd el a teszteket érthetően

## További információk

- [PHPUnit Dokumentáció](https://phpunit.de/documentation.html)
- [Testing Best Practices](BEST_PRACTICES.md#testing)
