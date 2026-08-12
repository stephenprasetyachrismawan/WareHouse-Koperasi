<?php

namespace App\Domain\Notifications\Push;

use App\Models\DeviceToken;

/**
 * The only sender ever used in automated tests — never the real FCM
 * implementation, which would require live credentials and make real
 * network calls. Bound in place of FcmPushNotificationSender in the test
 * service container where push delivery needs to be exercised.
 */
class FakePushNotificationSender implements PushNotificationSender
{
    /** @var list<array{deviceToken: DeviceToken, payload: PushPayload}> */
    public array $sent = [];

    private ?PushSendResult $nextResult = null;

    /** @var array<int, PushSendResult> */
    private array $resultsByDeviceTokenId = [];

    public function send(DeviceToken $deviceToken, PushPayload $payload): PushSendResult
    {
        $this->sent[] = ['deviceToken' => $deviceToken, 'payload' => $payload];

        return $this->resultsByDeviceTokenId[$deviceToken->id]
            ?? $this->nextResult
            ?? PushSendResult::sent('fake-message-id-'.count($this->sent));
    }

    public function willReturn(PushSendResult $result): void
    {
        $this->nextResult = $result;
    }

    public function willReturnForDevice(int $deviceTokenId, PushSendResult $result): void
    {
        $this->resultsByDeviceTokenId[$deviceTokenId] = $result;
    }
}
