<?php

namespace App\Domain\Notifications\Support;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Support\Collection;

/**
 * Server-side recipient resolution for business notifications. Recipients
 * are always derived from active warehouse membership + permission — never
 * accepted from client input, never resolved from role name alone when the
 * permission matrix is more precise.
 */
class RecipientResolver
{
    /**
     * @return Collection<int, User>
     */
    public function warehouseUsersWithPermission(int $warehouseId, Permission $permission): Collection
    {
        return WarehouseMembership::where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->filter(fn (WarehouseMembership $membership) => $membership->hasPermission($permission))
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
    }
}
