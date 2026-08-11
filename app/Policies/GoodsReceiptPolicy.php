<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\GoodsReceipt;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class GoodsReceiptPolicy
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
        return $this->hasPermission($user, Permission::ReceiptViewAny->value);
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        if (! $this->hasPermission($user, Permission::ReceiptView->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $goodsReceipt->warehouse_id;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::ReceiptCreate->value);
    }
}
