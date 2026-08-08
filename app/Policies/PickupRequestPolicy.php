<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PickupRequest;
use App\Models\User;

class PickupRequestPolicy
{
    private function hasPermission(User $user, Permission $permission): bool
    {
        $membership = $user->activeMembership();
        if (! $membership) {
            return false;
        }

        return $membership->hasPermission($permission);
    }

    private function isSameWarehouse(User $user, PickupRequest $pickupRequest): bool
    {
        return $user->activeMembership()?->warehouse_id === $pickupRequest->warehouse_id;
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, Permission::PickupRequestViewAny);
    }

    public function view(User $user, PickupRequest $pickupRequest): bool
    {
        return $this->viewAny($user) && $this->isSameWarehouse($user, $pickupRequest);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::PickupRequestCreate);
    }

    public function approve(User $user, ?PickupRequest $pickupRequest = null): bool
    {
        if (! $this->hasPermission($user, Permission::PickupRequestApprove)) {
            return false;
        }

        if ($pickupRequest) {
            return $this->isSameWarehouse($user, $pickupRequest);
        }

        return true;
    }

    public function prepare(User $user, ?PickupRequest $pickupRequest = null): bool
    {
        if (! $this->hasPermission($user, Permission::PickupRequestPrepare)) {
            return false;
        }

        if ($pickupRequest) {
            return $this->isSameWarehouse($user, $pickupRequest);
        }

        return true;
    }

    public function fulfill(User $user, PickupRequest $pickupRequest): bool
    {
        return $this->hasPermission($user, Permission::PickupRequestFulfill) &&
               $this->isSameWarehouse($user, $pickupRequest);
    }

    public function cancel(User $user, PickupRequest $pickupRequest): bool
    {
        // Users can cancel their own if they have permission, or maybe admins can cancel any.
        // Assuming the permission is required.
        return $this->hasPermission($user, Permission::PickupRequestCancel) &&
               $this->isSameWarehouse($user, $pickupRequest);
    }
}
