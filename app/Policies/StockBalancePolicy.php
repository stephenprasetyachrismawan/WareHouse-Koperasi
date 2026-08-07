<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\StockBalance;
use App\Models\User;

class StockBalancePolicy
{
    /**
     * Determine if the user can view stock balances.
     */
    public function viewAny(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::StockView);
    }

    /**
     * Determine if the user can view a specific stock balance.
     */
    public function view(User $user, StockBalance $stockBalance): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($stockBalance->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::StockView);
    }

    /**
     * Determine if the user can adjust stock (manual adjustment).
     */
    public function adjust(User $user, ?StockBalance $stockBalance = null): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($stockBalance !== null && $stockBalance->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::StockAdjust);
    }
}
