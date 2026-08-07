<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\StockTransaction;
use App\Models\User;

class StockTransactionPolicy
{
    /**
     * Determine if the user can view any stock transactions (ledger).
     */
    public function viewAny(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::StockLedgerView);
    }

    /**
     * Determine if the user can view a specific stock transaction.
     */
    public function view(User $user, StockTransaction $transaction): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($transaction->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::StockLedgerView);
    }

    /**
     * Determine if the user can record stock in (scan/manual).
     */
    public function recordIn(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::StockScanIn)
            || $membership->hasPermission(Permission::StockManage);
    }

    /**
     * Determine if the user can record stock out (scan/manual).
     */
    public function recordOut(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::StockScanOut)
            || $membership->hasPermission(Permission::StockManage);
    }

    /**
     * Determine if the user can record stock adjustments.
     */
    public function adjust(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::StockAdjust);
    }
}
