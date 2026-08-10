<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PurchaseRequestGroup;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PurchaseRequestGroupPolicy
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

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, Permission::PurchaseGroupViewAny->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseRequestGroup $group): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseGroupViewAny->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $group->warehouse_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::PurchaseGroupCreate->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseRequestGroup $group): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseGroupUpdate->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $group->warehouse_id;
    }
}
