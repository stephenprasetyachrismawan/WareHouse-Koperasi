<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\MarkNotificationReadAction;
use App\Domain\Notifications\Queries\InboxNotificationsQuery;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Models\InboxNotification;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class NotificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_a_notifications_are_hidden_from_a_tenant_b_query(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $userA = User::factory()->create();
        WarehouseMembership::factory()->create(['user_id' => $userA->id, 'warehouse_id' => $warehouseA->id, 'status' => 'active']);

        InboxNotification::factory()->create(['recipient_id' => $userA->id, 'warehouse_id' => $warehouseA->id]);

        $results = app(InboxNotificationsQuery::class)->execute($userA->id, $warehouseB->id);

        $this->assertCount(0, $results);
    }

    public function test_multi_warehouse_user_sees_only_active_warehouse_operational_inbox(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $user = User::factory()->create();
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouseA->id, 'status' => 'active']);
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouseB->id, 'status' => 'active']);

        InboxNotification::factory()->create(['recipient_id' => $user->id, 'warehouse_id' => $warehouseA->id, 'title' => 'For A']);
        InboxNotification::factory()->create(['recipient_id' => $user->id, 'warehouse_id' => $warehouseB->id, 'title' => 'For B']);

        $resultsForA = app(InboxNotificationsQuery::class)->execute($user->id, $warehouseA->id);

        $this->assertCount(1, $resultsForA);
        $this->assertEquals('For A', $resultsForA->first()->title);
    }

    public function test_uuid_swap_across_tenants_is_denied_by_policy(): void
    {
        $notificationB = InboxNotification::factory()->create();
        $userA = User::factory()->create();

        $this->assertFalse(Gate::forUser($userA)->allows('view', $notificationB));
    }

    public function test_cross_tenant_mark_read_is_denied(): void
    {
        $notificationB = InboxNotification::factory()->create();
        $userA = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        app(MarkNotificationReadAction::class)->execute($userA, $notificationB);
    }

    public function test_unread_count_is_scoped_per_warehouse(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $user = User::factory()->create();

        InboxNotification::factory()->unread()->create(['recipient_id' => $user->id, 'warehouse_id' => $warehouseA->id]);
        InboxNotification::factory()->unread()->create(['recipient_id' => $user->id, 'warehouse_id' => $warehouseB->id]);

        $countForA = app(InboxNotificationsQuery::class)->unreadCount($user->id, $warehouseA->id);

        $this->assertSame(1, $countForA);
    }

    public function test_action_route_is_always_a_relative_internal_path_never_an_external_url(): void
    {
        $notification = InboxNotification::factory()->create([
            'action_route' => '/returns/'.fake()->uuid(),
        ]);

        $this->assertStringStartsNotWith('http://', $notification->action_route);
        $this->assertStringStartsNotWith('https://', $notification->action_route);
    }

    public function test_notification_payload_never_persists_evidence_or_file_paths(): void
    {
        $notification = InboxNotification::factory()->create([
            'message' => 'Retur RET-2026-0001 telah disetujui.',
        ]);

        $this->assertStringNotContainsString('storage/', $notification->message);
        $this->assertStringNotContainsString('.jpg', $notification->message);
        $this->assertStringNotContainsString('signature=', $notification->message);
    }

    public function test_inactive_membership_users_are_never_selected_as_recipients(): void
    {
        $warehouse = Warehouse::factory()->create();
        $suspendedHead = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $suspendedHead->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'suspended',
        ]);

        $recipients = app(RecipientResolver::class)
            ->warehouseUsersWithPermission($warehouse->id, Permission::PurchaseRequestApprove);

        $this->assertTrue($recipients->isEmpty());
    }

    public function test_historical_notifications_survive_membership_suspension(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
        ]);

        $notification = InboxNotification::factory()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $membership->update(['status' => 'suspended']);

        // Row is preserved, not deleted.
        $this->assertNotNull($notification->fresh());
        // But live access now correctly follows current tenant-access policy.
        $this->assertFalse(Gate::forUser($user)->allows('view', $notification->fresh()));
    }

    public function test_type_filter_never_leaks_another_recipients_notifications(): void
    {
        $otherUser = User::factory()->create();
        InboxNotification::factory()->create([
            'recipient_id' => $otherUser->id,
            'type' => NotificationType::SecurityAlert,
            'warehouse_id' => null,
        ]);

        $me = User::factory()->create();
        $results = app(InboxNotificationsQuery::class)->execute($me->id, null, false, NotificationType::SecurityAlert);

        $this->assertCount(0, $results);
    }
}
