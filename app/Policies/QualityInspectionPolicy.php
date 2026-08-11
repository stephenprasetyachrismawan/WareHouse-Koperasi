<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\GoodsReceiptItem;
use App\Models\QualityInspection;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class QualityInspectionPolicy
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

    public function view(User $user, QualityInspection $qualityInspection): bool
    {
        if (! $this->hasPermission($user, Permission::ReceiptView->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $qualityInspection->warehouse_id;
    }

    /**
     * Determine whether the user may perform QC on the given receipt item.
     */
    public function create(User $user, GoodsReceiptItem $goodsReceiptItem): bool
    {
        if (! $this->hasPermission($user, Permission::ReceiptQc->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $goodsReceiptItem->warehouse_id;
    }
}
