<?php

namespace Tests\Feature\Notifications;

use App\Domain\Notifications\Push\FcmPushNotificationSender;
use App\Domain\Notifications\Push\PushNotificationSender;
use App\Domain\Notifications\Push\PushPayload;
use App\Enums\DeliveryStatus;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmPushNotificationSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_interface_resolves_to_the_fcm_implementation_by_default(): void
    {
        $this->assertInstanceOf(FcmPushNotificationSender::class, app(PushNotificationSender::class));
    }

    public function test_sending_without_configured_credentials_fails_retryably_without_a_live_network_call(): void
    {
        config(['services.fcm.credentials_path' => null]);
        Http::preventStrayRequests();

        $deviceToken = DeviceToken::factory()->create();
        $payload = new PushPayload('some-uuid', 'Title', 'Body');

        $result = app(FcmPushNotificationSender::class)->send($deviceToken, $payload);

        $this->assertSame(DeliveryStatus::FailedRetryable, $result->status);
        $this->assertSame('token-unavailable', $result->errorCode);
    }
}
