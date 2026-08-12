<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\RevokeDeviceTokenAction;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevokeDeviceTokenActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_revoke_their_own_device_token(): void
    {
        $user = User::factory()->create();
        $deviceToken = DeviceToken::factory()->for($user)->create();

        $result = app(RevokeDeviceTokenAction::class)->execute($user, $deviceToken);

        $this->assertFalse($result->isActive());
        $this->assertNotNull($deviceToken->fresh()->revoked_at);
    }

    public function test_a_different_user_cannot_revoke_someone_elses_device_token(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $deviceToken = DeviceToken::factory()->for($owner)->create();

        $this->expectException(AuthorizationException::class);

        try {
            app(RevokeDeviceTokenAction::class)->execute($attacker, $deviceToken);
        } finally {
            $this->assertTrue($deviceToken->fresh()->isActive());
        }
    }

    public function test_revoking_an_already_revoked_token_is_safe(): void
    {
        $user = User::factory()->create();
        $deviceToken = DeviceToken::factory()->for($user)->revoked()->create();
        $originalRevokedAt = $deviceToken->revoked_at;

        $this->travel(1)->hours();
        app(RevokeDeviceTokenAction::class)->execute($user, $deviceToken);

        $this->assertTrue($deviceToken->fresh()->revoked_at->equalTo($originalRevokedAt));
    }
}
