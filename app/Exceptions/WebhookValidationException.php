<?php

namespace App\Exceptions;

use Exception;

class WebhookValidationException extends Exception
{
    protected array $validationErrors;

    public function __construct(string $message = 'Webhook validation failed', array $validationErrors = [], int $code = 0, ?Exception $previous = null)
    {
        $this->validationErrors = $validationErrors;

        if (! empty($validationErrors)) {
            $message .= ': '.implode(', ', $validationErrors);
        }

        parent::__construct($message, $code, $previous);
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
