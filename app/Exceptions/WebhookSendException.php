<?php

namespace App\Exceptions;

use Exception;

class WebhookSendException extends Exception
{
    protected int $responseCode;

    protected string $responseBody;

    protected array $context;

    public function __construct(string $message = 'Webhook send failed', int $responseCode = 0, string $responseBody = '', array $context = [], int $code = 0, ?Exception $previous = null)
    {
        $this->responseCode = $responseCode;
        $this->responseBody = $responseBody;
        $this->context = $context;

        if ($responseCode > 0) {
            $message .= " (HTTP {$responseCode})";
        }

        parent::__construct($message, $code, $previous);
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
