<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ReturnRequest;
use App\Models\User;

class ReturnRequestPolicy
{
    private function hasPermission(User $user, Permission $permission): bool
    {
        $membership = $user->activeMembership();
        if (! $membership) {
            return false;
        }

        return $membership->hasPermission($permission);
    }

    private function isSameWarehouse(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->activeMembership()?->warehouse_id === $returnRequest->warehouse_id;
    }

    private function isOwnReturn(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->activeMembership()?->id === $returnRequest->cooperative_membership_id;
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, Permission::ReturnViewAny);
    }

    public function view(User $user, ReturnRequest $returnRequest): bool
    {
        if (! $this->viewAny($user) || ! $this->isSameWarehouse($user, $returnRequest)) {
            return false;
        }

        // A Koperasi membership may only see its own returns; staff/managerial
        // roles that hold return.view see every return in their warehouse.
        if ($user->activeMembership()?->hasPermission(Permission::ReturnCreate)
            && ! $user->activeMembership()?->hasPermission(Permission::ReturnVerify)) {
            return $this->isOwnReturn($user, $returnRequest);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::ReturnCreate);
    }

    public function verify(User $user, ReturnRequest $returnRequest): bool
    {
        return $this->hasPermission($user, Permission::ReturnVerify) && $this->isSameWarehouse($user, $returnRequest);
    }

    public function submitForApproval(User $user, ReturnRequest $returnRequest): bool
    {
        return $this->verify($user, $returnRequest);
    }
}
