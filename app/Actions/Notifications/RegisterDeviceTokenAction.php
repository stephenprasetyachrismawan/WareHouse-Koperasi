<?php

namespace App\Actions\Notifications;

use App\Domain\Notifications\ValueObjects\RegisterDeviceTokenInput;
use App\Models\DeviceToken;
use App\Models\User;

/**
 * Device ownership always comes from the authenticated actor, never a
 * client-supplied user_id. Identity is keyed by token_fingerprint (a
 * deterministic hash of the raw token) rather than the encrypted value
 * itself, since Laravel's encryption is randomized and can't be looked up
 * directly — this is what makes re-registering the same browser/device an
 * upsert instead of a duplicate row.
 */
class RegisterDeviceTokenAction
{
    private const MAX_ACTIVE_DEVICES_PER_USER = 10;

    public function execute(User $user, RegisterDeviceTokenInput $input): DeviceToken
    {
        $fingerprint = hash('sha256', $input->rawToken);

        $existing = DeviceToken::where('token_fingerprint', $fingerprint)->first();

        if ($existing) {
            return $this->reregister($existing, $user, $input);
        }

        $this->enforceDeviceLimit($user);

        return DeviceToken::create([
            'user_id' => $user->id,
            'provider' => $input->provider,
            'platform' => $input->platform,
            'encrypted_token' => $input->rawToken,
            'token_fingerprint' => $fingerprint,
            'device_name' => $input->deviceName,
            'browser' => $input->browser,
            'user_agent_summary' => $input->userAgentSummary,
            'consented_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Registration only ever runs from an explicit user action (enabling
     * push in the preferences UI), never a silent background call — so
     * reactivating a revoked token, or reassigning a shared-device token to
     * a different logged-in user, both correctly count as fresh consent.
     */
    private function reregister(DeviceToken $existing, User $user, RegisterDeviceTokenInput $input): DeviceToken
    {
        $isFreshConsent = $existing->user_id !== $user->id || ! $existing->isActive();

        $existing->fill([
            'user_id' => $user->id,
            'provider' => $input->provider,
            'platform' => $input->platform,
            'device_name' => $input->deviceName,
            'browser' => $input->browser,
            'user_agent_summary' => $input->userAgentSummary,
            'consented_at' => $isFreshConsent ? now() : ($existing->consented_at ?? now()),
            'last_seen_at' => now(),
            'revoked_at' => null,
        ]);
        $existing->save();

        return $existing;
    }

    private function enforceDeviceLimit(User $user): void
    {
        $activeCount = DeviceToken::forUser($user->id)->active()->count();

        if ($activeCount < self::MAX_ACTIVE_DEVICES_PER_USER) {
            return;
        }

        DeviceToken::forUser($user->id)->active()->oldest('last_seen_at')->first()
            ?->update(['revoked_at' => now()]);
    }
}
