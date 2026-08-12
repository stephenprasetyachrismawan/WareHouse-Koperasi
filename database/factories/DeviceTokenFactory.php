<?php

namespace Database\Factories;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceToken>
 */
class DeviceTokenFactory extends Factory
{
    public function definition(): array
    {
        // Obviously-fake, never a real provider token — this must never be
        // able to reach an actual FCM endpoint even by accident.
        $rawToken = 'fake-fcm-token-'.$this->faker->unique()->uuid();

        return [
            'user_id' => User::factory(),
            'provider' => 'fcm',
            'platform' => 'web',
            'encrypted_token' => $rawToken,
            'token_fingerprint' => hash('sha256', $rawToken),
            'device_name' => $this->faker->randomElement(['Chrome on Windows', 'Safari on iPhone', 'Firefox on Linux']),
            'browser' => $this->faker->randomElement(['Chrome', 'Safari', 'Firefox']),
            'user_agent_summary' => $this->faker->userAgent(),
            'consented_at' => now(),
            'last_seen_at' => now(),
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): self
    {
        return $this->state(fn (array $attributes) => ['revoked_at' => now()]);
    }
}
