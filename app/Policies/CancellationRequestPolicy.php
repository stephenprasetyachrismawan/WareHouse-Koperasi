<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\CancellationRequest;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class CancellationRequestPolicy
{
    private function hasPermission(User $user, string $permission): bool
    {
        if ($user->activeMembership() === null) {
            return false;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, Permission::PurchaseRequestCancel->value);
    }

    public function view(User $user, CancellationRequest $cancellationRequest): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseRequestView->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $cancellationRequest->warehouse_id;
    }

    public function decide(User $user, CancellationRequest $cancellationRequest): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseRequestCancel->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $cancellationRequest->warehouse_id;
    }
}
