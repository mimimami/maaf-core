<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * Forbidden Exception
 * 
 * Thrown when access is forbidden.
 * 
 * @version 1.0.0
 */
final class ForbiddenException extends \Exception
{
    public function __construct(string $message = 'Forbidden', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
