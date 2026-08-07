<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Determine if the user can view any items in their active warehouse.
     */
    public function viewAny(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::ItemViewAny)
            || $membership->hasPermission(Permission::StockView);
    }

    /**
     * Determine if the user can view a specific item.
     */
    public function view(User $user, Item $item): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        // Tenant isolation: item must belong to user's active warehouse
        if ($item->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::ItemViewAny)
            || $membership->hasPermission(Permission::StockView);
    }

    /**
     * Determine if the user can create items.
     */
    public function create(User $user): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        return $membership->hasPermission(Permission::ItemCreate);
    }

    /**
     * Determine if the user can update the item.
     */
    public function update(User $user, Item $item): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($item->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::ItemUpdate);
    }

    /**
     * Determine if the user can archive the item.
     */
    public function archive(User $user, Item $item): bool
    {
        $membership = $user->activeMembership();

        if (! $membership || ! $membership->isActive()) {
            return false;
        }

        if ($item->warehouse_id !== $membership->warehouse_id) {
            return false;
        }

        return $membership->hasPermission(Permission::ItemArchive);
    }
}
