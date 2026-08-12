<?php

namespace Tests\Feature\Notifications;

use App\Models\InboxNotification;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDeepLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    private function withActiveMembership(User $user): Warehouse
    {
        $warehouse = Warehouse::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
        ]);

        return $warehouse;
    }

    public function test_the_owner_is_redirected_to_the_notifications_action_route(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
        ]);
        $notification = InboxNotification::factory()->unread()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'action_route' => '/procurement/requests/some-uuid',
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->uuid}")
            ->assertRedirect('/procurement/requests/some-uuid');
    }

    public function test_following_the_deep_link_marks_the_notification_read(): void
    {
        $user = User::factory()->create();
        $warehouse = $this->withActiveMembership($user);
        $notification = InboxNotification::factory()->unread()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->actingAs($user)->get("/notifications/{$notification->uuid}");

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_notification_with_no_action_route_redirects_to_the_inbox(): void
    {
        $user = User::factory()->create();
        $warehouse = $this->withActiveMembership($user);
        $notification = InboxNotification::factory()->create([
            'recipient_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'action_route' => null,
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->uuid}")
            ->assertRedirect(route('inbox'));
    }

    public function test_a_different_user_cannot_view_someone_elses_notification_via_deep_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->withActiveMembership($other);
        $notification = InboxNotification::factory()->unread()->create(['recipient_id' => $owner->id]);

        $this->actingAs($other)
            ->get("/notifications/{$notification->uuid}")
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_an_unauthenticated_request_is_redirected_to_login(): void
    {
        $notification = InboxNotification::factory()->create();

        $this->get("/notifications/{$notification->uuid}")
            ->assertRedirect(route('login'));
    }

    public function test_a_nonexistent_notification_uuid_returns_not_found(): void
    {
        $user = User::factory()->create();
        $this->withActiveMembership($user);

        $this->actingAs($user)
            ->get('/notifications/'.Str::uuid())
            ->assertNotFound();
    }
}
