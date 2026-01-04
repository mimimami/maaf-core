# Middleware Pipeline - Teljes Implementáció

## Áttekintés

A MAAF Core teljes middleware pipeline rendszert tartalmaz, amely lehetővé teszi több middleware láncolását egy kérés-válasz ciklusban.

## Komponensek

### 1. MiddlewareInterface

Az interface, amelyet a middleware osztályoknak implementálniuk kell:

```php
use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

class MyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Before controller execution
        // ... your logic ...
        
        $response = $next($request);
        
        // After controller execution
        // ... your logic ...
        
        return $response;
    }
}
```

### 2. MiddlewarePipeline

A pipeline osztály, amely kezeli a middleware stack-et:

```php
$pipeline = new MiddlewarePipeline();
$pipeline->add($middleware1);
$pipeline->add($middleware2);
$pipeline->add($middleware3);

$response = $pipeline->execute($request, $finalHandler);
```

### 3. HttpKernel Integráció

A HttpKernel automatikusan használja a MiddlewarePipeline-t:

```php
$kernel = new HttpKernel($router, $resolver, $container);

// Middleware-ek hozzáadása
$kernel->addMiddleware(new LoggingMiddleware());
$kernel->addMiddleware(new CorsMiddleware());
```

## Használat

### Egyszerű Middleware (Callable)

```php
$kernel->addMiddleware(function (Request $request, callable $next): Response {
    // Before
    error_log('Request: ' . $request->getPath());
    
    $response = $next($request);
    
    // After
    error_log('Response: ' . $response->getStatusCode());
    
    return $response;
});
```

### Middleware Osztály

```php
use MAAF\Core\Http\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->getHeader('Authorization');
        
        if (!$token) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        // Validate token...
        
        return $next($request);
    }
}

$kernel->addMiddleware(new AuthMiddleware());
```

### Több Middleware Láncolása

```php
// Middleware-ek hozzáadása sorrendben
$kernel->addMiddleware(new LoggingMiddleware());      // 1. fut le
$kernel->addMiddleware(new CorsMiddleware());         // 2. fut le
$kernel->addMiddleware(new RateLimitingMiddleware()); // 3. fut le
$kernel->addMiddleware(new AuthMiddleware());         // 4. fut le

// Vagy egyszerre
$kernel->addMiddlewares([
    new LoggingMiddleware(),
    new CorsMiddleware(),
    new RateLimitingMiddleware(),
    new AuthMiddleware(),
]);
```

## Execution Flow

```
Request
  ↓
Middleware 1 (before)
  ↓
Middleware 2 (before)
  ↓
Middleware 3 (before)
  ↓
Controller
  ↓
Middleware 3 (after)
  ↓
Middleware 2 (after)
  ↓
Middleware 1 (after)
  ↓
Response
```

## Példa: Teljes Middleware Pipeline

```php
<?php

use MAAF\Core\Http\HttpKernel;
use MAAF\Core\Http\Middleware\LoggingMiddleware;
use MAAF\Core\Http\Middleware\CorsMiddleware;
use MAAF\Core\Http\Middleware\RateLimitingMiddleware;

// HttpKernel létrehozása
$kernel = new HttpKernel($router, $resolver, $container);

// Middleware pipeline beállítása
$kernel->addMiddleware(new LoggingMiddleware());
$kernel->addMiddleware(new CorsMiddleware(['*']));
$kernel->addMiddleware(new RateLimitingMiddleware(100, 60));

// Custom middleware
$kernel->addMiddleware(function (Request $request, callable $next): Response {
    // Request timing
    $start = microtime(true);
    
    $response = $next($request);
    
    $duration = microtime(true) - $start;
    $response = $response->withHeader('X-Response-Time', (string) $duration);
    
    return $response;
});

// Request kezelés
$request = Request::fromGlobals();
$response = $kernel->handle($request);
$response->send();
```

## Built-in Middlewares

### LoggingMiddleware

Logolja a kéréseket és válaszokat:

```php
$kernel->addMiddleware(new LoggingMiddleware());
```

### CorsMiddleware

CORS header-ek kezelése:

```php
$kernel->addMiddleware(new CorsMiddleware(
    allowedOrigins: ['*'],
    allowedMethods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization']
));
```

### RateLimitingMiddleware

Rate limiting implementáció:

```php
$kernel->addMiddleware(new RateLimitingMiddleware(
    maxRequests: 100,
    windowSeconds: 60
));
```

## Advanced: Conditional Middleware

```php
$kernel->addMiddleware(function (Request $request, callable $next): Response {
    // Csak bizonyos route-okhoz
    if (str_starts_with($request->getPath(), '/api/')) {
        // API-specific middleware logic
    }
    
    return $next($request);
});
```

## Best Practices

1. **Middleware sorrend:** Fontos a middleware-ek sorrendje
   - Logging → CORS → Rate Limiting → Auth → Controller

2. **Early Return:** Ha egy middleware megállítja a folyamatot, ne hívja meg a `$next`-et:
   ```php
   if (!$authorized) {
       return Response::json(['error' => 'Unauthorized'], 401);
   }
   ```

3. **Response módosítás:** A middleware módosíthatja a választ:
   ```php
   $response = $next($request);
   return $response->withHeader('X-Custom-Header', 'value');
   ```

4. **Hibakezelés:** Middleware-ek kezelhetnek exception-öket:
   ```php
   try {
       return $next($request);
   } catch (UnauthorizedException $e) {
       return Response::json(['error' => 'Unauthorized'], 401);
   }
   ```

