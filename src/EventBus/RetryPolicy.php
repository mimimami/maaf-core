<?php

declare(strict_types=1);

namespace MAAF\Core\EventBus;

/**
 * Retry Policy
 * 
 * Újrapróbálkozási stratégia.
 * 
 * @version 2.0.0
 */
final class RetryPolicy
{
    /**
     * @param int $maxRetries Maximum number of retries
     * @param int $initialDelay Initial delay in seconds
     * @param float $backoffMultiplier Backoff multiplier (exponential backoff)
     * @param int $maxDelay Maximum delay in seconds
     * @param bool $exponentialBackoff Use exponential backoff
     */
    public function __construct(
        public readonly int $maxRetries = 3,
        public readonly int $initialDelay = 1,
        public readonly float $backoffMultiplier = 2.0,
        public readonly int $maxDelay = 300,
        public readonly bool $exponentialBackoff = true
    ) {
    }

    /**
     * Get delay for retry attempt
     * 
     * @param int $retryCount Current retry count
     * @return int Delay in seconds
     */
    public function getDelay(int $retryCount): int
    {
        if (!$this->exponentialBackoff) {
            return $this->initialDelay;
        }

        $delay = (int) ($this->initialDelay * pow($this->backoffMultiplier, $retryCount));
        return min($delay, $this->maxDelay);
    }

    /**
     * Check if should retry
     * 
     * @param int $retryCount Current retry count
     * @return bool
     */
    public function shouldRetry(int $retryCount): bool
    {
        return $retryCount < $this->maxRetries;
    }

    /**
     * Create default retry policy
     * 
     * @return self
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create no retry policy
     * 
     * @return self
     */
    public static function noRetry(): self
    {
        return new self(maxRetries: 0);
    }

    /**
     * Create aggressive retry policy
     * 
     * @return self
     */
    public static function aggressive(): self
    {
        return new self(
            maxRetries: 10,
            initialDelay: 1,
            backoffMultiplier: 1.5,
            maxDelay: 600
        );
    }
}
