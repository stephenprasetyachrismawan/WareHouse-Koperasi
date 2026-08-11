<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\InboxNotification;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InboxNotificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_uuid_on_create(): void
    {
        $notification = InboxNotification::factory()->create();

        $this->assertNotNull($notification->uuid);
    }

    public function test_it_casts_type_and_read_at(): void
    {
        $notification = InboxNotification::factory()->approvalRequired()->create();

        $this->assertEquals(NotificationType::ApprovalRequired, $notification->type);
        $this->assertTrue($notification->isUnread());

        $notification->update(['read_at' => now()]);
        $this->assertFalse($notification->fresh()->isUnread());
    }

    public function test_recipient_can_view_and_mark_read_their_own_notification(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
        ]);

        $notification = InboxNotification::factory()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $notification));
        $this->assertTrue(Gate::forUser($user)->allows('markAsRead', $notification));
    }

    public function test_another_user_cannot_view_or_mark_read(): void
    {
        $notification = InboxNotification::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $notification));
        $this->assertFalse(Gate::forUser($otherUser)->allows('markAsRead', $notification));
    }

    public function test_recipient_without_active_membership_in_the_notifications_warehouse_is_denied(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'suspended',
        ]);

        $notification = InboxNotification::factory()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('view', $notification));
    }

    public function test_platform_notification_with_null_warehouse_skips_membership_check(): void
    {
        $user = User::factory()->create();

        $notification = InboxNotification::factory()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => null,
            'type' => NotificationType::SecurityAlert,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $notification));
    }

    public function test_correlation_key_is_unique_per_recipient(): void
    {
        $user = User::factory()->create();

        InboxNotification::factory()->create(['recipient_id' => $user->id, 'correlation_key' => 'fixed-key']);

        $this->expectException(QueryException::class);
        InboxNotification::factory()->create(['recipient_id' => $user->id, 'correlation_key' => 'fixed-key']);
    }
}
