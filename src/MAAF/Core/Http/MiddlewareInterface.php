<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * Middleware Interface
 * 
 * Defines the contract for HTTP middleware.
 */
interface MiddlewareInterface
{
    /**
     * Process the request and return a response.
     * 
     * @param Request $request The HTTP request
     * @param callable(Request): Response $next The next middleware in the pipeline
     * @return Response The HTTP response
     */
    public function handle(Request $request, callable $next): Response;
}

