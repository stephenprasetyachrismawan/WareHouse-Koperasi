<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WarehouseLocation;

class WarehouseLocationPolicy
{
    /**
     * Determine if the user can view any locations.
     */
    public function viewAny(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::LocationViewAny);
    }

    /**
     * Determine if the user can view a specific location.
     */
    public function view(User $user, WarehouseLocation $location): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($location->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::LocationViewAny);
    }

    /**
     * Determine if the user can create locations.
     */
    public function create(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::LocationManage);
    }

    /**
     * Determine if the user can update the location.
     */
    public function update(User $user, WarehouseLocation $location): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($location->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::LocationManage);
    }
}
