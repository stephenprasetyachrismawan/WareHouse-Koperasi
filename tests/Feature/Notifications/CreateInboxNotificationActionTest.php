<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Enums\NotificationType;
use App\Models\InboxNotification;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateInboxNotificationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_notification_with_full_context(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $pr = PurchaseRequest::factory()->create(['warehouse_id' => $warehouse->id]);

        $notification = app(CreateInboxNotificationAction::class)->execute(new CreateInboxNotificationInput(
            recipientId: $user->id,
            warehouseId: $warehouse->id,
            type: NotificationType::ApprovalRequired,
            title: 'Persetujuan Diperlukan',
            message: 'PR-001 menunggu persetujuan Anda.',
            correlationKey: "purchase_request:{$pr->id}:approval_required:{$user->id}",
            subjectType: PurchaseRequest::class,
            subjectId: $pr->id,
            actionRoute: '/procurement/requests/'.$pr->uuid,
        ));

        $this->assertInstanceOf(InboxNotification::class, $notification);
        $this->assertEquals($user->id, $notification->recipient_id);
        $this->assertEquals($warehouse->id, $notification->warehouse_id);
        $this->assertEquals(NotificationType::ApprovalRequired, $notification->type);
        $this->assertEquals(PurchaseRequest::class, $notification->subject_type);
        $this->assertEquals($pr->id, $notification->subject_id);
        $this->assertNull($notification->read_at);
        $this->assertSame(1, InboxNotification::count());
    }

    public function test_it_is_idempotent_for_the_same_correlation_key_and_recipient(): void
    {
        $user = User::factory()->create();
        $action = app(CreateInboxNotificationAction::class);

        $input = new CreateInboxNotificationInput(
            recipientId: $user->id,
            warehouseId: null,
            type: NotificationType::SecurityAlert,
            title: 'Test',
            message: 'Test message',
            correlationKey: 'fixed-correlation-key',
        );

        $first = $action->execute($input);
        $second = $action->execute($input);

        $this->assertEquals($first->id, $second->id);
        $this->assertSame(1, InboxNotification::count());
    }

    public function test_concurrent_duplicate_insert_resolves_to_the_existing_row_without_throwing(): void
    {
        $user = User::factory()->create();
        $action = app(CreateInboxNotificationAction::class);

        $input = new CreateInboxNotificationInput(
            recipientId: $user->id,
            warehouseId: null,
            type: NotificationType::SecurityAlert,
            title: 'Test',
            message: 'Test message',
            correlationKey: 'race-key',
        );

        // Simulate a second worker having already inserted the row.
        InboxNotification::create([
            'recipient_id' => $user->id,
            'correlation_key' => 'race-key',
            'type' => NotificationType::SecurityAlert,
            'title' => 'Test',
            'message' => 'Test message',
        ]);

        $result = $action->execute($input);

        $this->assertNotNull($result);
        $this->assertSame(1, InboxNotification::count());
    }

    public function test_different_recipients_can_share_the_same_correlation_key(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $action = app(CreateInboxNotificationAction::class);

        $action->execute(new CreateInboxNotificationInput(
            recipientId: $userA->id,
            warehouseId: null,
            type: NotificationType::ApprovalRequired,
            title: 'Test',
            message: 'Test',
            correlationKey: 'shared-key',
        ));
        $action->execute(new CreateInboxNotificationInput(
            recipientId: $userB->id,
            warehouseId: null,
            type: NotificationType::ApprovalRequired,
            title: 'Test',
            message: 'Test',
            correlationKey: 'shared-key',
        ));

        $this->assertSame(2, InboxNotification::count());
    }
}
