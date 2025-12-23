<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    protected int $retryAfter;

    protected string $limitType;

    protected int $currentCount;

    protected int $maxAllowed;

    public function __construct(string $message = 'Rate limit exceeded', string $limitType = 'default', int $retryAfter = 60, int $currentCount = 0, int $maxAllowed = 0, int $code = 429, ?Exception $previous = null)
    {
        $this->retryAfter = $retryAfter;
        $this->limitType = $limitType;
        $this->currentCount = $currentCount;
        $this->maxAllowed = $maxAllowed;

        if ($currentCount > 0 && $maxAllowed > 0) {
            $message = sprintf(
                '%s: %s limit exceeded (%d/%d). Retry after %d seconds.',
                $message,
                ucfirst($limitType),
                $currentCount,
                $maxAllowed,
                $retryAfter
            );
        }

        parent::__construct($message, $code, $previous);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public function getLimitType(): string
    {
        return $this->limitType;
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }

    public function getMaxAllowed(): int
    {
        return $this->maxAllowed;
    }
}
