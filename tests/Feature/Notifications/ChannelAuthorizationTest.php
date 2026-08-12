<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
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

    private function authorize(User $actingAs, int $channelUserId): TestResponse
    {
        return $this->actingAs($actingAs)->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$channelUserId}.notifications",
            'socket_id' => '1234.5678',
        ]);
    }

    public function test_user_can_authorize_their_own_notification_channel(): void
    {
        $user = User::factory()->create();

        $this->authorize($user, $user->id)->assertOk();
    }

    public function test_user_cannot_authorize_another_users_notification_channel(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->authorize($user, $other->id)->assertForbidden();
    }

    public function test_unauthenticated_request_is_denied(): void
    {
        $other = User::factory()->create();

        // Laravel's Pusher-protocol broadcaster throws AccessDeniedHttpException
        // (403) for a guarded channel with no resolvable user — there is no
        // distinct 401 path in this flow.
        $this->post('/broadcasting/auth', [
            'channel_name' => "private-user.{$other->id}.notifications",
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    public function test_forged_channel_with_non_numeric_id_is_denied(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/broadcasting/auth', [
            'channel_name' => 'private-user.not-a-number.notifications',
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    public function test_inactive_user_is_denied_even_for_their_own_channel(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);

        $this->authorize($user, $user->id)->assertForbidden();
    }
}
