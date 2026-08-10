<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\CancellationRequest;
use App\Models\User;

class CancellationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasActiveMembership() && $user->hasPermission(Permission::PurchaseRequestCancel); // same as approve/cancel basically
    }

    public function view(User $user, CancellationRequest $cancellationRequest): bool
    {
        if (! $user->hasActiveMembership() || ! $user->hasPermission(Permission::PurchaseRequestView)) {
            return false;
        }

        return $user->active_warehouse_id === $cancellationRequest->warehouse_id;
    }

    public function decide(User $user, CancellationRequest $cancellationRequest): bool
    {
        if (! $user->hasActiveMembership() || ! $user->hasPermission(Permission::PurchaseRequestCancel)) {
            return false;
        }

        return $user->active_warehouse_id === $cancellationRequest->warehouse_id;
    }
}
