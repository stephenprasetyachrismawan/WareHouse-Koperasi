<?php

namespace App\Domain\Notifications\ValueObjects;

use App\Enums\NotificationType;

readonly class CreateInboxNotificationInput
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $recipientId,
        public ?int $warehouseId,
        public NotificationType $type,
        public string $title,
        public string $message,
        public string $correlationKey,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public ?string $actionRoute = null,
        public ?array $metadata = null,
    ) {}
}
