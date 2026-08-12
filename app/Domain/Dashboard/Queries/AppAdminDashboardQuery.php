<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Queries;

use App\Domain\Inventory\Queries\CriticalStockItemsQuery;
use App\Enums\Permission;
use App\Enums\PurchaseRequestStatus;
use App\Enums\WarehouseRole;
use App\Models\PurchaseRequest;
use App\Models\WarehouseMembership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AppAdminDashboardQuery
{
    public function __construct(
        private readonly CriticalStockItemsQuery $criticalStock,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(WarehouseMembership $membership): array
    {
        $warehouseId = $membership->warehouse_id;

        $memberships = WarehouseMembership::query()
            ->where('warehouse_id', $warehouseId);

        $operational = null;
        if ($this->hasExplicitPermission($membership, Permission::StockView)) {
            $operational = [
                'criticalStockCount' => $this->criticalStock->count($warehouseId),
                'inProgressPurchaseRequestCount' => $this->hasExplicitPermission($membership, Permission::PurchaseRequestViewAny)
                    ? PurchaseRequest::forWarehouse($warehouseId)
                        ->whereNotIn('status', [
                            PurchaseRequestStatus::Completed->value,
                            PurchaseRequestStatus::Rejected->value,
                            PurchaseRequestStatus::Cancelled->value,
                        ])
                        ->count()
                    : null,
            ];
        }

        return [
            'warehouse' => $membership->warehouse,
            'membership' => $membership,
            'updatedAt' => Carbon::now(),
            'activeUserCount' => (clone $memberships)->where('status', 'active')->distinct('user_id')->count('user_id'),
            'activeMembershipCount' => (clone $memberships)->where('status', 'active')->count(),
            'suspendedUserCount' => (clone $memberships)->where('status', 'suspended')->distinct('user_id')->count('user_id'),
            'roleDistribution' => (clone $memberships)
                ->select('role', DB::raw('count(*) as total'))
                ->where('status', 'active')
                ->groupBy('role')
                ->orderBy('role')
                ->pluck('total', 'role'),
            'operational' => $operational,
        ];
    }

    private function hasExplicitPermission(WarehouseMembership $membership, Permission $permission): bool
    {
        if ($membership->role !== WarehouseRole::AppAdmin->value && $membership->role !== WarehouseRole::AppAdmin) {
            return $membership->hasPermission($permission);
        }

        return is_array($membership->permissions)
            && in_array($permission->value, $membership->permissions, true);
    }
}
