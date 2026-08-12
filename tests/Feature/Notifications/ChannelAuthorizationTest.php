<?php

namespace Tests\Feature\Notifications;

use App\Domain\Notifications\Events\InboxNotificationCreated;
use App\Enums\NotificationType;
use App\Enums\WarehouseRole;
use App\Models\InboxNotification;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml pins BROADCAST_CONNECTION=null for the rest of the
        // suite (so tests never attempt a real network broadcast) — but the
        // null/log drivers' auth() is a deliberate no-op, so it can't
        // exercise real channel-authorization logic at all. Only a
        // Pusher-protocol-compatible driver (which Reverb is) actually runs
        // the registered Broadcast::channel() callback via HTTP. No network
        // call happens here — auth() is pure server-side logic.
        config(['broadcasting.default' => 'reverb']);

        // routes/channels.php only ran once, at application boot, against
        // whichever driver was default at that time (the 'null' driver from
        // phpunit.xml). Switching the default above resolves a brand new,
        // separate broadcaster instance whose channel registry is empty, so
        // the pattern must be re-registered against it here.
        require base_path('routes/channels.php');
    }

    private function authorizeWarehouse(User $actingAs, int $channelUserId, int $warehouseId): TestResponse
    {
        return $this->actingAs($actingAs)->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$channelUserId}.warehouse.{$warehouseId}.notifications",
            'socket_id' => '1234.5678',
        ]);
    }

    public function test_user_can_authorize_their_own_active_warehouse_channel(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->authorizeWarehouse($user, $user->id, $warehouse->id)->assertOk();
    }

    public function test_user_cannot_authorize_another_users_notification_channel(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->authorizeWarehouse($user, $other->id, $warehouse->id)->assertForbidden();
    }

    public function test_user_cannot_authorize_a_warehouse_without_active_membership(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $this->authorizeWarehouse($user, $user->id, $warehouse->id)->assertForbidden();
    }

    public function test_unauthenticated_request_is_denied(): void
    {
        $other = User::factory()->create();

        // Laravel's Pusher-protocol broadcaster throws AccessDeniedHttpException
        // (403) for a guarded channel with no resolvable user — there is no
        // distinct 401 path in this flow.
        $this->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$other->id}.warehouse.999999.notifications",
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    public function test_forged_channel_with_non_numeric_id_is_denied(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/broadcasting/auth', [
            'channel_name' => 'private-user.not-a-number.warehouse.1.notifications',
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    public function test_inactive_user_is_denied_even_for_their_own_channel(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['status' => 'suspended']);
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
        ]);

        $this->authorizeWarehouse($user, $user->id, $warehouse->id)->assertForbidden();
    }

    public function test_notification_event_uses_the_notification_warehouse_in_its_channel(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $notification = InboxNotification::factory()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'type' => NotificationType::ApprovalRequired,
        ]);

        $channels = (new InboxNotificationCreated($notification->id))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame(
            "private-user.{$user->id}.warehouse.{$warehouse->id}.notifications",
            $channels[0]->name,
        );
    }
}
