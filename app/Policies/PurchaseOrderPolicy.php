<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PurchaseOrder;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PurchaseOrderPolicy
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
        return $this->hasPermission($user, Permission::PurchaseOrderViewAny->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseOrderView->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $purchaseOrder->warehouse_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::PurchaseOrderCreate->value);
    }

    /**
     * Determine whether the user can send the model.
     */
    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseOrderSend->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $purchaseOrder->warehouse_id;
    }
}
