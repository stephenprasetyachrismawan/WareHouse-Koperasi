<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Determine if the user can view any suppliers.
     */
    public function viewAny(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::SupplierViewAny);
    }

    /**
     * Determine if the user can view a specific supplier.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($supplier->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::SupplierViewAny);
    }

    /**
     * Determine if the user can create suppliers.
     */
    public function create(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::SupplierManage);
    }

    /**
     * Determine if the user can update the supplier.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($supplier->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::SupplierManage);
    }
}
