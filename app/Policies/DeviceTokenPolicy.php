<?php

namespace App\Policies;

use App\Models\DeviceToken;
use App\Models\User;

class DeviceTokenPolicy
{
    public function view(User $user, DeviceToken $deviceToken): bool
    {
        return $deviceToken->user_id === $user->id;
    }

    public function revoke(User $user, DeviceToken $deviceToken): bool
    {
        return $deviceToken->user_id === $user->id;
    }
}
