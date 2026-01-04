<?php

declare(strict_types=1);

namespace MAAF\Core\Http\Middleware;

use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

/**
 * Rate Limiting Middleware
 * 
 * Example middleware that implements rate limiting.
 * Note: This is a simple in-memory implementation.
 * For production, use Redis or another distributed cache.
 */
final class RateLimitingMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string, array{count: int, reset: int}>
     */
    private static array $requests = [];

    /**
     * @param int $maxRequests Maximum requests per window
     * @param int $windowSeconds Time window in seconds
     */
    public function __construct(
        private int $maxRequests = 100,
        private int $windowSeconds = 60
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $key = $this->getClientKey($request);
        $now = time();

        // Clean up old entries
        $this->cleanup($now);

        // Check rate limit
        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = ['count' => 0, 'reset' => $now + $this->windowSeconds];
        }

        if (self::$requests[$key]['count'] >= $this->maxRequests) {
            return Response::json([
                'error' => 'Rate limit exceeded',
                'retry_after' => self::$requests[$key]['reset'] - $now
            ], 429)
                ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('X-RateLimit-Reset', (string) self::$requests[$key]['reset']);
        }

        // Increment counter
        self::$requests[$key]['count']++;

        // Process request
        $response = $next($request);

        // Add rate limit headers
        $remaining = max(0, $this->maxRequests - self::$requests[$key]['count']);
        
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining)
            ->withHeader('X-RateLimit-Reset', (string) self::$requests[$key]['reset']);
    }

    private function getClientKey(Request $request): string
    {
        // Use IP address as key (in production, consider user ID for authenticated requests)
        return $request->server['REMOTE_ADDR'] ?? 'unknown';
    }

    private function cleanup(int $now): void
    {
        foreach (self::$requests as $key => $data) {
            if ($data['reset'] < $now) {
                unset(self::$requests[$key]);
            }
        }
    }
}

