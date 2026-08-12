<?php

namespace Tests\Feature\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceTokenModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_raw_token_is_never_stored_in_plaintext(): void
    {
        $rawToken = 'fake-fcm-token-plaintext-check';

        DeviceToken::factory()->create([
            'encrypted_token' => $rawToken,
            'token_fingerprint' => hash('sha256', $rawToken),
        ]);

        $rawColumnValue = DB::table('device_tokens')->value('encrypted_token');

        $this->assertNotSame($rawToken, $rawColumnValue);
        $this->assertStringNotContainsString($rawToken, (string) $rawColumnValue);
    }

    public function test_the_encrypted_token_column_is_hidden_from_array_and_json_output(): void
    {
        $deviceToken = DeviceToken::factory()->create();

        $this->assertArrayNotHasKey('encrypted_token', $deviceToken->toArray());
        $this->assertStringNotContainsString('encrypted_token', $deviceToken->toJson());
    }

    public function test_decrypting_the_model_attribute_returns_the_original_raw_token(): void
    {
        $rawToken = 'fake-fcm-token-roundtrip-check';

        $deviceToken = DeviceToken::factory()->create([
            'encrypted_token' => $rawToken,
            'token_fingerprint' => hash('sha256', $rawToken),
        ]);

        $this->assertSame($rawToken, $deviceToken->fresh()->encrypted_token);
    }

    public function test_is_active_reflects_revoked_at(): void
    {
        $active = DeviceToken::factory()->create();
        $revoked = DeviceToken::factory()->revoked()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($revoked->isActive());
    }

    public function test_active_scope_excludes_revoked_tokens(): void
    {
        $user = User::factory()->create();
        $active = DeviceToken::factory()->for($user)->create();
        DeviceToken::factory()->for($user)->revoked()->create();

        $result = DeviceToken::forUser($user->id)->active()->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($active));
    }
}
