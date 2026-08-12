<?php

namespace App\Actions\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RevokeDeviceTokenAction
{
    public function execute(User $actor, DeviceToken $deviceToken): DeviceToken
    {
        Gate::forUser($actor)->authorize('revoke', $deviceToken);

        if ($deviceToken->isActive()) {
            $deviceToken->update(['revoked_at' => now()]);
        }

        return $deviceToken;
    }
}
