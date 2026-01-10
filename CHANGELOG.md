# Changelog

## [2.1.0] - 2024-01-XX

### Added

#### Testing Toolkit 1.0
- ✅ `ModuleTestHelper` - Modul teszteléshez segédeszközök
- ✅ `UseCaseTestHelper` - Use case teszteléshez segédeszközök
- ✅ `AdapterMockHelper` - Adapter mockoláshoz segédeszközök
- ✅ `MockQueueAdapter` - Mock queue adapter teszteléshez
- ✅ `MockEventBusAdapter` - Mock event bus adapter teszteléshez
- ✅ `TestCase` - Alap teszt osztály helper-ekkel

## [2.0.0] - 2024-01-XX

### Added

#### Async EventBus 2.0
- ✅ `AsyncEventBusInterface` - Aszinkron EventBus interface
- ✅ `AsyncEventBus` - Aszinkron EventBus implementáció
- ✅ `EventMessage` - Esemény üzenet osztály
- ✅ `QueueAdapterInterface` - Queue adapter interface
- ✅ `RabbitMQAdapter` - RabbitMQ adapter implementáció
- ✅ `RedisStreamsAdapter` - Redis Streams adapter implementáció
- ✅ `RetryPolicy` - Retry logika exponenciális backoff-fal
- ✅ Dead Letter Queue támogatás
- ✅ Modulonkénti routing
- ✅ `EventConsumeCommand` - CLI parancs event consumer futtatásához

### Changed
- EventBus kiterjesztve AsyncEventBusInterface-tel

## [1.0.0] - 2024-01-XX

### Added

#### DI Container 1.0
- ✅ `ContainerInterface` - Stabil API a Dependency Injection Container számára
- ✅ `Container` - PHP-DI alapú implementáció
- ✅ `NotFoundException` - Exception amikor egy entry nem található

#### Module Loader 3.0
- ✅ `ModuleLoader` - Automatikus modul betöltő rendszer
- ✅ `ModuleInterface` - Interface amit minden modulnak implementálnia kell
- ✅ Támogatás service regisztrációhoz
- ✅ Támogatás route regisztrációhoz
- ✅ Támogatás event listener regisztrációhoz

#### EventBus 1.0
- ✅ `EventBusInterface` - Stabil API az eseménykezelő rendszer számára
- ✅ `EventBus` - Eseménykezelő implementáció
- ✅ Priority-based listener execution
- ✅ Subscribe/Unsubscribe/Publish műveletek

#### Config Engine 1.0
- ✅ `ConfigInterface` - Stabil API a konfigurációs motor számára
- ✅ `Config` - Konfigurációs motor implementáció
- ✅ Dot notation támogatás
- ✅ File-based és array-based konfiguráció betöltés

#### HTTP Kernel 1.0
- ✅ `Request` - HTTP Request osztály
- ✅ `Response` - HTTP Response osztály
- ✅ `MiddlewareInterface` - Middleware interface
- ✅ `Kernel` - HTTP kernel implementáció
- ✅ `Router` - FastRoute alapú routing rendszer
- ✅ `Application` - Fő alkalmazás osztály
- ✅ HTTP Exception osztályok (NotFoundException, UnauthorizedException, ForbiddenException)

#### CLI 1.0
- ✅ `CommandInterface` - CLI parancsok interface-je
- ✅ `CommandRunner` - Parancsok futtatásáért felelős osztály
- ✅ `Cli` - Fő CLI osztály
- ✅ Built-in parancsok (help, route:list)

### Changed
- N/A (első stabil kiadás)

### Fixed
- N/A (első stabil kiadás)
