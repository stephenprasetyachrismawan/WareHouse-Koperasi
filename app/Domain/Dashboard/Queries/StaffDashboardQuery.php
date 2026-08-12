<?php

namespace App\Domain\Dashboard\Queries;

use App\Domain\Inventory\Queries\CriticalStockItemsQuery;
use App\Domain\Pickup\Queries\PickupTaskSummaryQuery;
use App\Domain\Procurement\Queries\PendingQualityInspectionsQuery;
use App\Domain\Procurement\Queries\PurchaseRequestInProgressByItemQuery;
use App\Domain\Returns\Queries\PendingReturnVerificationsQuery;
use App\Enums\Permission;
use App\Models\WarehouseMembership;
use Illuminate\Support\Carbon;

class StaffDashboardQuery
{
    public function __construct(
        private readonly PickupTaskSummaryQuery $pickupTasks,
        private readonly PendingQualityInspectionsQuery $pendingQc,
        private readonly PendingReturnVerificationsQuery $pendingReturnVerifications,
        private readonly CriticalStockItemsQuery $criticalStock,
        private readonly PurchaseRequestInProgressByItemQuery $inProgressByItem,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(WarehouseMembership $membership): array
    {
        $warehouseId = $membership->warehouse_id;
        $can = static fn (Permission $permission): bool => $membership->hasPermission($permission);

        return [
            'warehouse' => $membership->warehouse,
            'updatedAt' => Carbon::now(),
            'pickupTasks' => $can(Permission::PickupRequestViewAny) ? $this->pickupTasks->execute($warehouseId) : null,
            'qcPendingCount' => $can(Permission::ReceiptViewAny) ? $this->pendingQc->execute($warehouseId, 1)->total() : null,
            'returnVerificationCount' => $can(Permission::ReturnVerify) ? $this->pendingReturnVerifications->count($warehouseId) : null,
            'criticalStockCount' => $can(Permission::StockView) ? $this->criticalStock->count($warehouseId) : null,
            'inProgressByItem' => $can(Permission::PurchaseRequestViewAny) ? $this->inProgressByItem->execute($warehouseId) : null,
        ];
    }
}
