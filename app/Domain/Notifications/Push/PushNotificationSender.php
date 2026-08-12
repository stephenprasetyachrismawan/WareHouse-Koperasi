<?php

namespace App\Domain\Notifications\Push;

use App\Models\DeviceToken;

/**
 * Domain/app code depends on this interface only — never a concrete SDK or
 * HTTP client directly, so the provider can be swapped or faked in tests
 * without touching delivery logic.
 */
interface PushNotificationSender
{
    public function send(DeviceToken $deviceToken, PushPayload $payload): PushSendResult;
}
