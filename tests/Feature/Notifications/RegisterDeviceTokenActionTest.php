<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\RegisterDeviceTokenAction;
use App\Domain\Notifications\ValueObjects\RegisterDeviceTokenInput;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterDeviceTokenActionTest extends TestCase
{
    use RefreshDatabase;

    private function input(string $rawToken = 'fake-fcm-token-1'): RegisterDeviceTokenInput
    {
        return new RegisterDeviceTokenInput(
            rawToken: $rawToken,
            provider: 'fcm',
            platform: 'web',
            deviceName: 'Chrome on Windows',
            browser: 'Chrome',
            userAgentSummary: 'Mozilla/5.0',
        );
    }

    public function test_it_creates_a_new_device_token_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $deviceToken = app(RegisterDeviceTokenAction::class)->execute($user, $this->input());

        $this->assertSame($user->id, $deviceToken->user_id);
        $this->assertSame(hash('sha256', 'fake-fcm-token-1'), $deviceToken->token_fingerprint);
        $this->assertNotNull($deviceToken->consented_at);
        $this->assertNotNull($deviceToken->last_seen_at);
        $this->assertTrue($deviceToken->isActive());
    }

    public function test_registering_the_same_token_again_does_not_create_a_duplicate_row(): void
    {
        $user = User::factory()->create();
        $action = app(RegisterDeviceTokenAction::class);

        $action->execute($user, $this->input());
        $action->execute($user, $this->input());

        $this->assertSame(1, DeviceToken::count());
    }

    public function test_registering_the_same_token_again_refreshes_last_seen_at_without_resetting_original_consent(): void
    {
        $user = User::factory()->create();
        $action = app(RegisterDeviceTokenAction::class);

        $first = $action->execute($user, $this->input());
        $originalConsentedAt = $first->consented_at;

        $this->travel(1)->hours();
        $second = $action->execute($user, $this->input());

        $this->assertTrue($second->consented_at->equalTo($originalConsentedAt));
        $this->assertTrue($second->last_seen_at->greaterThan($first->last_seen_at));
    }

    public function test_reregistering_a_revoked_token_reactivates_it_with_fresh_consent(): void
    {
        $user = User::factory()->create();
        $action = app(RegisterDeviceTokenAction::class);

        $deviceToken = $action->execute($user, $this->input());
        $deviceToken->update(['revoked_at' => now()]);

        $this->travel(1)->hours();
        $reactivated = $action->execute($user, $this->input());

        $this->assertTrue($reactivated->is($deviceToken));
        $this->assertTrue($reactivated->isActive());
        $this->assertTrue($reactivated->consented_at->greaterThan($deviceToken->consented_at));
    }

    public function test_registering_a_token_previously_owned_by_another_user_reassigns_ownership(): void
    {
        $originalOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        $action = app(RegisterDeviceTokenAction::class);

        $deviceToken = $action->execute($originalOwner, $this->input());
        $reassigned = $action->execute($newOwner, $this->input());

        $this->assertSame(1, DeviceToken::count());
        $this->assertTrue($reassigned->is($deviceToken));
        $this->assertSame($newOwner->id, $reassigned->fresh()->user_id);
    }

    public function test_a_different_raw_token_creates_a_second_row_for_the_same_user(): void
    {
        $user = User::factory()->create();
        $action = app(RegisterDeviceTokenAction::class);

        $action->execute($user, $this->input('fake-fcm-token-a'));
        $action->execute($user, $this->input('fake-fcm-token-b'));

        $this->assertSame(2, DeviceToken::forUser($user->id)->count());
    }

    public function test_registering_an_eleventh_device_revokes_the_least_recently_seen_active_device(): void
    {
        $user = User::factory()->create();
        $action = app(RegisterDeviceTokenAction::class);

        $oldest = null;
        foreach (range(1, 10) as $i) {
            $token = $action->execute($user, $this->input("fake-fcm-token-{$i}"));
            if ($i === 1) {
                $oldest = $token;
            }
            $this->travel(1)->minutes();
        }

        $action->execute($user, $this->input('fake-fcm-token-11'));

        $this->assertFalse($oldest->fresh()->isActive());
        $this->assertSame(10, DeviceToken::forUser($user->id)->active()->count());
    }

    public function test_the_raw_token_is_never_persisted_in_plaintext(): void
    {
        $user = User::factory()->create();

        app(RegisterDeviceTokenAction::class)->execute($user, $this->input('fake-fcm-token-plaintext-check'));

        $rawColumnValue = DB::table('device_tokens')->value('encrypted_token');

        $this->assertNotSame('fake-fcm-token-plaintext-check', $rawColumnValue);
    }
}
