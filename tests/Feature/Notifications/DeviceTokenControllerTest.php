<?php

namespace Tests\Feature\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private function withActiveMembership(User $user): User
    {
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_an_authenticated_user_can_register_a_device_token(): void
    {
        $user = $this->withActiveMembership(User::factory()->create());

        $response = $this->actingAs($user)->postJson('/notifications/devices', [
            'token' => 'fake-fcm-token-registration-test',
            'provider' => 'fcm',
            'platform' => 'web',
            'device_name' => 'Chrome on Windows',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['uuid']);

        $deviceToken = DeviceToken::first();
        $this->assertSame($user->id, $deviceToken->user_id);
        $this->assertSame(hash('sha256', 'fake-fcm-token-registration-test'), $deviceToken->token_fingerprint);
    }

    public function test_registration_ignores_any_client_supplied_user_id(): void
    {
        $user = $this->withActiveMembership(User::factory()->create());
        $other = User::factory()->create();

        $this->actingAs($user)->postJson('/notifications/devices', [
            'token' => 'fake-fcm-token-ownership-test',
            'provider' => 'fcm',
            'user_id' => $other->id,
        ])->assertCreated();

        $this->assertSame($user->id, DeviceToken::first()->user_id);
    }

    public function test_registration_requires_authentication(): void
    {
        $this->postJson('/notifications/devices', [
            'token' => 'fake-fcm-token-guest-test',
            'provider' => 'fcm',
        ])->assertUnauthorized();

        $this->assertSame(0, DeviceToken::count());
    }

    public function test_registration_validates_the_provider(): void
    {
        $user = $this->withActiveMembership(User::factory()->create());

        $this->actingAs($user)->postJson('/notifications/devices', [
            'token' => 'fake-fcm-token-invalid-provider',
            'provider' => 'not-a-real-provider',
        ])->assertUnprocessable();
    }

    public function test_registration_requires_a_token(): void
    {
        $user = $this->withActiveMembership(User::factory()->create());

        $this->actingAs($user)->postJson('/notifications/devices', [
            'provider' => 'fcm',
        ])->assertUnprocessable();
    }

    public function test_the_owner_can_revoke_their_own_device(): void
    {
        $user = $this->withActiveMembership(User::factory()->create());
        $deviceToken = DeviceToken::factory()->for($user)->create();

        $this->actingAs($user)
            ->deleteJson("/notifications/devices/{$deviceToken->uuid}")
            ->assertOk()
            ->assertJson(['revoked' => true]);

        $this->assertFalse($deviceToken->fresh()->isActive());
    }

    public function test_a_different_user_cannot_revoke_someone_elses_device(): void
    {
        $owner = User::factory()->create();
        $attacker = $this->withActiveMembership(User::factory()->create());
        $deviceToken = DeviceToken::factory()->for($owner)->create();

        $this->actingAs($attacker)
            ->deleteJson("/notifications/devices/{$deviceToken->uuid}")
            ->assertForbidden();

        $this->assertTrue($deviceToken->fresh()->isActive());
    }

    public function test_revocation_requires_authentication(): void
    {
        $deviceToken = DeviceToken::factory()->create();

        $this->deleteJson("/notifications/devices/{$deviceToken->uuid}")->assertUnauthorized();

        $this->assertTrue($deviceToken->fresh()->isActive());
    }
}
