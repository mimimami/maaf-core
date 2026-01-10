<?php

declare(strict_types=1);

namespace MAAF\Core\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Container Not Found Exception
 * 
 * Thrown when an entry is not found in the container.
 * 
 * @version 1.0.0
 */
final class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    public function __construct(string $id, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Entry "%s" not found in container', $id),
            0,
            $previous
        );
    }
}
