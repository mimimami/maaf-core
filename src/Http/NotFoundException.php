<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * Not Found Exception
 * 
 * Thrown when a route or resource is not found.
 * 
 * @version 1.0.0
 */
final class NotFoundException extends \Exception
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
