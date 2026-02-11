<?php

namespace App\Exceptions;

use Exception;

class NotificationDeliveryException extends Exception
{
    protected string $channel;

    protected ?string $recipient;

    protected ?string $notificationType;

    protected array $deliveryDetails;

    public function __construct(string $message = 'Notification delivery failed', string $channel = 'unknown', ?string $recipient = null, ?string $notificationType = null, array $deliveryDetails = [], int $code = 0, ?Exception $previous = null)
    {
        $this->channel = $channel;
        $this->recipient = $recipient;
        $this->notificationType = $notificationType;
        $this->deliveryDetails = $deliveryDetails;

        $contextParts = [];
        if ($channel !== 'unknown') {
            $contextParts[] = "channel: {$channel}";
        }
        if ($notificationType !== null) {
            $contextParts[] = "type: {$notificationType}";
        }

        if (! empty($contextParts)) {
            $message .= ' ('.implode(', ', $contextParts).')';
        }

        parent::__construct($message, $code, $previous);
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function getNotificationType(): ?string
    {
        return $this->notificationType;
    }

    public function getDeliveryDetails(): array
    {
        return $this->deliveryDetails;
    }
}
