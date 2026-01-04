<?php

declare(strict_types=1);

namespace MAAF\Core\Http\Middleware;

use MAAF\Core\Http\MiddlewareInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;

/**
 * CORS Middleware
 * 
 * Example middleware that handles CORS headers.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string> $allowedOrigins Allowed origins
     * @param array<string> $allowedMethods Allowed HTTP methods
     * @param array<string> $allowedHeaders Allowed headers
     */
    public function __construct(
        private array $allowedOrigins = ['*'],
        private array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        private array $allowedHeaders = ['Content-Type', 'Authorization']
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflight($request);
        }

        // Process request
        $response = $next($request);

        // Add CORS headers
        return $this->addCorsHeaders($request, $response);
    }

    private function handlePreflight(Request $request): Response
    {
        $headers = [
            'Access-Control-Allow-Origin' => $this->getAllowedOrigin($request),
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
            'Access-Control-Max-Age' => '86400',
        ];

        return Response::empty(204)->withHeader('Access-Control-Allow-Origin', $headers['Access-Control-Allow-Origin'])
            ->withHeader('Access-Control-Allow-Methods', $headers['Access-Control-Allow-Methods'])
            ->withHeader('Access-Control-Allow-Headers', $headers['Access-Control-Allow-Headers'])
            ->withHeader('Access-Control-Max-Age', $headers['Access-Control-Max-Age']);
    }

    private function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $this->getAllowedOrigin($request);
        
        return $response->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    private function getAllowedOrigin(Request $request): string
    {
        $origin = $request->getHeader('Origin');
        
        if ($origin === null) {
            return $this->allowedOrigins[0] ?? '*';
        }

        if (in_array('*', $this->allowedOrigins, true)) {
            return '*';
        }

        if (in_array($origin, $this->allowedOrigins, true)) {
            return $origin;
        }

        return $this->allowedOrigins[0] ?? '*';
    }
}

