<?php

namespace Tests\Feature\Notifications;

use App\Domain\Notifications\Push\FakePushNotificationSender;
use App\Domain\Notifications\Push\PushNotificationSender;
use App\Domain\Notifications\Push\PushSendResult;
use App\Enums\DeliveryStatus;
use App\Enums\NotificationType;
use App\Enums\WarehouseRole;
use App\Jobs\DeliverPushNotificationJob;
use App\Models\DeviceToken;
use App\Models\InboxNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeliverPushNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $kepalaGudang;

    private DeviceToken $deviceToken;

    private InboxNotification $notification;

    private FakePushNotificationSender $sender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->kepalaGudang = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);
        $this->deviceToken = DeviceToken::factory()->for($this->kepalaGudang)->create();
        $this->notification = InboxNotification::factory()->create([
            'recipient_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => NotificationType::ApprovalRequired,
            'title' => 'Persetujuan Purchase Request Diperlukan',
            'message' => 'Purchase Request PR-2026-0099 (Budi Santoso) menunggu persetujuan Anda.',
        ]);

        $this->sender = new FakePushNotificationSender;
        $this->app->instance(PushNotificationSender::class, $this->sender);
    }

    private function runJob(): void
    {
        $job = new DeliverPushNotificationJob($this->notification->id, $this->deviceToken->id);
        $this->app->call([$job, 'handle']);
    }

    public function test_it_sends_push_and_records_a_sent_delivery(): void
    {
        $this->runJob();

        $delivery = NotificationDelivery::first();

        $this->assertNotNull($delivery);
        $this->assertSame(DeliveryStatus::Sent, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->sent_at);
        $this->assertNotNull($delivery->provider_message_id);
        $this->assertCount(1, $this->sender->sent);
    }

    public function test_the_push_payload_is_minimal_and_generic_not_the_inbox_message(): void
    {
        $this->runJob();

        $payload = $this->sender->sent[0]['payload'];

        $this->assertSame($this->notification->uuid, $payload->notificationUuid);
        $this->assertStringNotContainsString('PR-2026-0099', $payload->body);
        $this->assertStringNotContainsString('Budi Santoso', $payload->body);
        $this->assertSame('Persetujuan Diperlukan', $payload->title);
    }

    public function test_it_updates_the_device_tokens_last_used_at_on_success(): void
    {
        $this->assertNull($this->deviceToken->last_used_at);

        $this->runJob();

        $this->assertNotNull($this->deviceToken->fresh()->last_used_at);
    }

    public function test_running_twice_does_not_create_a_duplicate_delivery_row_or_resend(): void
    {
        $this->runJob();
        $this->runJob();

        $this->assertSame(1, NotificationDelivery::count());
        $this->assertCount(1, $this->sender->sent);
    }

    public function test_a_permanent_failure_marks_delivery_failed_permanent_and_revokes_only_that_device(): void
    {
        $otherDevice = DeviceToken::factory()->for($this->kepalaGudang)->create();
        $this->sender->willReturn(PushSendResult::failedPermanent('unregistered'));

        $this->runJob();

        $delivery = NotificationDelivery::first();
        $this->assertSame(DeliveryStatus::FailedPermanent, $delivery->status);
        $this->assertSame('unregistered', $delivery->last_error_code);
        $this->assertNotNull($delivery->failed_at);
        $this->assertFalse($this->deviceToken->fresh()->isActive());
        $this->assertTrue($otherDevice->fresh()->isActive());
    }

    public function test_a_retryable_failure_marks_delivery_failed_retryable_and_throws_for_the_queue_to_retry(): void
    {
        $this->sender->willReturn(PushSendResult::failedRetryable('unavailable'));

        $this->expectException(RuntimeException::class);

        try {
            $this->runJob();
        } finally {
            $delivery = NotificationDelivery::first();
            $this->assertSame(DeliveryStatus::FailedRetryable, $delivery->status);
            $this->assertSame('unavailable', $delivery->last_error_code);
            $this->assertTrue($this->deviceToken->fresh()->isActive());
        }
    }

    public function test_a_retried_delivery_increments_attempts_on_the_same_row(): void
    {
        $this->sender->willReturn(PushSendResult::failedRetryable('unavailable'));

        try {
            $this->runJob();
        } catch (RuntimeException) {
        }
        try {
            $this->runJob();
        } catch (RuntimeException) {
        }

        $this->assertSame(1, NotificationDelivery::count());
        $this->assertSame(2, NotificationDelivery::first()->attempts);
    }

    public function test_a_permanently_failed_delivery_is_never_retried(): void
    {
        $this->sender->willReturn(PushSendResult::failedPermanent('unregistered'));
        $this->runJob();

        $this->sender->sent = [];
        $this->runJob();

        $this->assertCount(0, $this->sender->sent);
        $this->assertSame(1, NotificationDelivery::first()->attempts);
    }

    public function test_failed_hook_marks_the_delivery_permanent_once_retries_are_exhausted(): void
    {
        $this->sender->willReturn(PushSendResult::failedRetryable('unavailable'));
        try {
            $this->runJob();
        } catch (RuntimeException) {
        }

        $job = new DeliverPushNotificationJob($this->notification->id, $this->deviceToken->id);
        $job->failed(new RuntimeException('exhausted'));

        $delivery = NotificationDelivery::first();
        $this->assertSame(DeliveryStatus::FailedPermanent, $delivery->status);
    }

    public function test_it_does_nothing_if_the_notification_no_longer_exists(): void
    {
        $job = new DeliverPushNotificationJob(999999, $this->deviceToken->id);
        $this->app->call([$job, 'handle']);

        $this->assertSame(0, NotificationDelivery::count());
        $this->assertCount(0, $this->sender->sent);
    }

    public function test_it_does_nothing_if_the_device_token_is_revoked(): void
    {
        $this->deviceToken->update(['revoked_at' => now()]);

        $this->runJob();

        $this->assertSame(0, NotificationDelivery::count());
        $this->assertCount(0, $this->sender->sent);
    }

    public function test_it_revalidates_eligibility_and_skips_if_a_preference_disabled_push_after_dispatch(): void
    {
        NotificationPreference::factory()->for($this->kepalaGudang)->disabled()->create([
            'warehouse_id' => $this->warehouse->id,
            'notification_type' => NotificationType::ApprovalRequired,
            'channel' => 'push',
        ]);

        $this->runJob();

        $this->assertSame(0, NotificationDelivery::count());
        $this->assertCount(0, $this->sender->sent);
    }

    public function test_it_revalidates_eligibility_and_skips_if_membership_was_revoked_after_dispatch(): void
    {
        WarehouseMembership::where('user_id', $this->kepalaGudang->id)->update(['status' => 'suspended']);

        $this->runJob();

        $this->assertSame(0, NotificationDelivery::count());
        $this->assertCount(0, $this->sender->sent);
    }
}
