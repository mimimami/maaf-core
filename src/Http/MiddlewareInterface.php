<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * Middleware Interface
 * 
 * Interface for HTTP middleware.
 * 
 * @version 1.0.0
 */
interface MiddlewareInterface
{
    /**
     * Handle the request
     * 
     * @param Request $request HTTP request
     * @param callable $next Next middleware handler
     * @return Response HTTP response
     */
    public function handle(Request $request, callable $next): Response;
}
