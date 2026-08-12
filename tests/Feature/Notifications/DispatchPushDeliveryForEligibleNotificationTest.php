<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Push\FakePushNotificationSender;
use App\Domain\Notifications\Push\PushNotificationSender;
use App\Domain\Notifications\Push\PushPayload;
use App\Domain\Notifications\Push\PushSendResult;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Enums\NotificationType;
use App\Enums\WarehouseRole;
use App\Models\DeviceToken;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchPushDeliveryForEligibleNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $kepalaGudang;

    private FakePushNotificationSender $sender;

    protected function setUp(): void
    {
        parent::setUp();

        // sync queue connection (per phpunit.xml) executes DeliverPushNotificationJob
        // inline, so a bound fake sender is required before any notification
        // is created in these tests.
        $this->sender = new FakePushNotificationSender;
        $this->app->instance(PushNotificationSender::class, $this->sender);

        $this->warehouse = Warehouse::factory()->create();
        $this->kepalaGudang = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);
    }

    private function input(): CreateInboxNotificationInput
    {
        return new CreateInboxNotificationInput(
            recipientId: $this->kepalaGudang->id,
            warehouseId: $this->warehouse->id,
            type: NotificationType::ApprovalRequired,
            title: 'Persetujuan Diperlukan',
            message: 'Ada permintaan yang menunggu persetujuan Anda.',
            correlationKey: 'dispatch-test-'.uniqid(),
        );
    }

    public function test_creating_an_eligible_notification_dispatches_push_to_every_active_device(): void
    {
        $deviceA = DeviceToken::factory()->for($this->kepalaGudang)->create();
        $deviceB = DeviceToken::factory()->for($this->kepalaGudang)->create();

        app(CreateInboxNotificationAction::class)->execute($this->input());

        $this->assertSame(2, NotificationDelivery::count());
        $this->assertCount(2, $this->sender->sent);
        $sentDeviceIds = collect($this->sender->sent)->map(fn ($s) => $s['deviceToken']->id)->sort()->values();
        $this->assertEquals([$deviceA->id, $deviceB->id], $sentDeviceIds->sort()->values()->all());
    }

    public function test_creating_an_eligible_notification_never_pushes_to_a_revoked_device(): void
    {
        DeviceToken::factory()->for($this->kepalaGudang)->revoked()->create();

        app(CreateInboxNotificationAction::class)->execute($this->input());

        $this->assertSame(0, NotificationDelivery::count());
        $this->assertCount(0, $this->sender->sent);
    }

    public function test_a_recipient_with_no_device_gets_no_push_but_the_notification_still_persists(): void
    {
        $notification = app(CreateInboxNotificationAction::class)->execute($this->input());

        $this->assertNotNull($notification->id);
        $this->assertSame(0, NotificationDelivery::count());
        $this->assertCount(0, $this->sender->sent);
    }

    public function test_a_non_push_eligible_notification_type_never_dispatches_push(): void
    {
        DeviceToken::factory()->for($this->kepalaGudang)->create();

        $input = new CreateInboxNotificationInput(
            recipientId: $this->kepalaGudang->id,
            warehouseId: $this->warehouse->id,
            type: NotificationType::PurchaseRequestStatus,
            title: 'Status Purchase Request',
            message: 'Status diperbarui.',
            correlationKey: 'dispatch-test-status-'.uniqid(),
        );

        app(CreateInboxNotificationAction::class)->execute($input);

        $this->assertSame(0, NotificationDelivery::count());
        $this->assertCount(0, $this->sender->sent);
    }

    public function test_a_sender_failure_never_prevents_the_inbox_notification_from_persisting(): void
    {
        DeviceToken::factory()->for($this->kepalaGudang)->create();
        // The fake throws to simulate a completely broken provider.
        $this->app->instance(PushNotificationSender::class, new class implements PushNotificationSender
        {
            public function send(DeviceToken $deviceToken, PushPayload $payload): PushSendResult
            {
                throw new \RuntimeException('provider is completely down');
            }
        });

        $notification = app(CreateInboxNotificationAction::class)->execute($this->input());

        $this->assertNotNull($notification->id);
        $this->assertDatabaseHas('inbox_notifications', ['id' => $notification->id]);
    }
}
