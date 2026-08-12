<?php

namespace App\Domain\Notifications\Push;

use App\Enums\DeliveryStatus;

/**
 * What a sender reports back — never the raw provider response, which may
 * contain sensitive data. errorCode is a short, sanitized classification
 * (e.g. "unregistered", "invalid-argument", "unavailable"), safe to log and
 * store.
 */
readonly class PushSendResult
{
    private function __construct(
        public DeliveryStatus $status,
        public ?string $providerMessageId,
        public ?string $errorCode,
    ) {}

    public static function sent(string $providerMessageId): self
    {
        return new self(DeliveryStatus::Sent, $providerMessageId, null);
    }

    public static function failedRetryable(string $errorCode): self
    {
        return new self(DeliveryStatus::FailedRetryable, null, $errorCode);
    }

    public static function failedPermanent(string $errorCode): self
    {
        return new self(DeliveryStatus::FailedPermanent, null, $errorCode);
    }
}
