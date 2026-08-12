<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Push\FakePushNotificationSender;
use App\Domain\Notifications\Push\PushNotificationSender;
use App\Domain\Notifications\Support\PushEligibilityPolicy;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Enums\NotificationType;
use App\Enums\WarehouseRole;
use App\Jobs\DeliverPushNotificationJob;
use App\Models\DeviceToken;
use App\Models\InboxNotification;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Push eligibility must always be re-derived from the InboxNotification's
 * own warehouse_id, never from "does this user hold ANY active membership
 * somewhere" — a multi-warehouse Kepala Gudang must only be push-eligible
 * for decisions in the specific warehouse where they hold the permission.
 */
class PushTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_tokens_have_no_warehouse_column_and_are_never_warehouse_tagged(): void
    {
        $this->assertFalse(Schema::hasColumn('device_tokens', 'warehouse_id'));
    }

    public function test_a_multi_warehouse_head_is_push_eligible_only_in_the_warehouse_where_they_hold_the_permission(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $head = User::factory()->create();

        WarehouseMembership::factory()->create([
            'user_id' => $head->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);
        WarehouseMembership::factory()->create([
            'user_id' => $head->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);
        DeviceToken::factory()->for($head)->create();

        $notificationForA = InboxNotification::factory()->create([
            'recipient_id' => $head->id,
            'warehouse_id' => $warehouseA->id,
            'type' => NotificationType::ApprovalRequired,
        ]);
        $notificationForB = InboxNotification::factory()->create([
            'recipient_id' => $head->id,
            'warehouse_id' => $warehouseB->id,
            'type' => NotificationType::ApprovalRequired,
        ]);

        $eligibility = app(PushEligibilityPolicy::class);

        $this->assertTrue($eligibility->isEligible($notificationForA));
        $this->assertFalse($eligibility->isEligible($notificationForB));
    }

    public function test_dispatching_push_for_a_warehouse_a_notification_never_reaches_a_warehouse_b_only_recipient(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $headA = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $headA->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);
        DeviceToken::factory()->for($headA)->create();

        $headB = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $headB->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);
        DeviceToken::factory()->for($headB)->create();

        $sender = new FakePushNotificationSender;
        $this->app->instance(PushNotificationSender::class, $sender);

        app(CreateInboxNotificationAction::class)->execute(
            new CreateInboxNotificationInput(
                recipientId: $headA->id,
                warehouseId: $warehouseA->id,
                type: NotificationType::ApprovalRequired,
                title: 'Persetujuan Diperlukan',
                message: 'Ada permintaan yang menunggu persetujuan Anda.',
                correlationKey: 'tenant-isolation-'.uniqid(),
            )
        );

        $this->assertSame(1, NotificationDelivery::count());
        $delivery = NotificationDelivery::first();
        $this->assertSame($headA->id, $delivery->deviceToken->user_id);
        $this->assertNotSame($headB->id, $delivery->deviceToken->user_id);
    }

    public function test_the_job_derives_warehouse_context_strictly_from_the_reloaded_notification_never_ambient_state(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $head = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $head->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);
        $deviceToken = DeviceToken::factory()->for($head)->create();
        $notification = InboxNotification::factory()->create([
            'recipient_id' => $head->id,
            'warehouse_id' => $warehouseA->id,
            'type' => NotificationType::ApprovalRequired,
        ]);

        $sender = new FakePushNotificationSender;
        $this->app->instance(PushNotificationSender::class, $sender);

        // No authenticated user, no session, no "current warehouse" — a
        // queued job has none of that. The job must still resolve correctly
        // using only the IDs it was constructed with.
        $this->assertGuest();

        $job = new DeliverPushNotificationJob($notification->id, $deviceToken->id);
        $this->app->call([$job, 'handle']);

        $this->assertCount(1, $sender->sent);
        $this->assertSame($notification->uuid, $sender->sent[0]['payload']->notificationUuid);
    }
}
