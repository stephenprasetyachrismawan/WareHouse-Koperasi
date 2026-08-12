<?php

namespace App\Domain\Notifications\Push;

/**
 * Deliberately smaller than the Inbox payload: a generic per-category title
 * and body (never the specific request number, requester identity, or
 * reason), plus the notification UUID so the client can resolve the safe
 * internal deep link itself after authenticating — never a raw route or
 * file URL travels through the push provider.
 */
readonly class PushPayload
{
    public function __construct(
        public string $notificationUuid,
        public string $title,
        public string $body,
    ) {}
}
