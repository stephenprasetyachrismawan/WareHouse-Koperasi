<?php

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Broadcast;

/**
 * Tenant notification channels are keyed by recipient and warehouse. The
 * browser-side active-warehouse filter remains a UX optimization, but the
 * channel authorization itself is the security boundary.
 */
Broadcast::channel('user.{id}.warehouse.{warehouseId}.notifications', function (User $user, string $id, string $warehouseId) {
    if (! ctype_digit($id) || (int) $user->id !== (int) $id) {
        return false;
    }

    if (! ctype_digit($warehouseId) || ! $user->isActive()) {
        return false;
    }

    return Warehouse::query()
        ->whereKey((int) $warehouseId)
        ->where('status', 'active')
        ->whereExists(fn ($query) => $query
            ->from('warehouse_memberships')
            ->whereColumn('warehouse_memberships.warehouse_id', 'warehouses.id')
            ->where('warehouse_memberships.user_id', $user->id)
            ->where('warehouse_memberships.status', 'active'))
        ->exists();
});

Broadcast::channel('user.{id}.platform.notifications', function (User $user, string $id) {
    return ctype_digit($id)
        && (int) $user->id === (int) $id
        && $user->isActive();
});
