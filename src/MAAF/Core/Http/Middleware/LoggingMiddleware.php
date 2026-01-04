<?php

declare(strict_types=1);

namespace MAAF\Core\Http\Middleware;

use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

/**
 * Logging Middleware
 * 
 * Example middleware that logs requests.
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Log request (in production, use proper logging)
        if (getenv('APP_ENV') !== 'production') {
            error_log(sprintf(
                '[%s] %s %s',
                date('Y-m-d H:i:s'),
                $request->getMethod(),
                $request->getPath()
            ));
        }

        // Continue to next middleware or controller
        $response = $next($request);

        // Log response status
        if (getenv('APP_ENV') !== 'production') {
            error_log(sprintf(
                '[%s] Response: %d',
                date('Y-m-d H:i:s'),
                $response->getStatusCode()
            ));
        }

        return $response;
    }
}

