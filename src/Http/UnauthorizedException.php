<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * Unauthorized Exception
 * 
 * Thrown when authentication is required but not provided.
 * 
 * @version 1.0.0
 */
final class UnauthorizedException extends \Exception
{
    public function __construct(string $message = 'Unauthorized', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
