<?php

namespace App\Domain\Dashboard\Queries;

use App\Domain\Approvals\Queries\PendingApprovalsSummaryQuery;
use App\Domain\Inventory\Queries\CriticalStockItemsQuery;
use App\Domain\Pickup\Queries\PickupTaskSummaryQuery;
use App\Domain\Procurement\Queries\ProcurementAttentionQuery;
use App\Domain\Returns\Queries\ReturnAttentionQuery;
use App\Enums\Permission;
use App\Models\WarehouseMembership;
use Illuminate\Support\Carbon;

class HeadDashboardQuery
{
    public function __construct(
        private readonly PendingApprovalsSummaryQuery $pendingApprovals,
        private readonly CriticalStockItemsQuery $criticalStock,
        private readonly PickupTaskSummaryQuery $pickupTasks,
        private readonly ProcurementAttentionQuery $procurementAttention,
        private readonly ReturnAttentionQuery $returnAttention,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(WarehouseMembership $membership): array
    {
        $warehouseId = $membership->warehouse_id;
        $can = static fn (Permission $permission): bool => $membership->hasPermission($permission);
        $canRequests = $can(Permission::PurchaseRequestViewAny);
        $canOrders = $can(Permission::PurchaseOrderViewAny);

        return [
            'warehouse' => $membership->warehouse,
            'updatedAt' => Carbon::now(),
            'pendingApprovals' => $this->pendingApprovals->execute($warehouseId, $membership),
            'criticalStockCount' => $can(Permission::StockView) ? $this->criticalStock->count($warehouseId) : null,
            'backorderedPickupCount' => $can(Permission::PickupRequestViewAny) ? $this->pickupTasks->execute($warehouseId)['backordered'] : null,
            'procurementAttention' => $this->procurementAttention->execute($warehouseId, $canRequests, $canOrders),
            'replacementPendingCount' => $can(Permission::ReturnViewAny) ? $this->returnAttention->replacementPendingCount($warehouseId) : null,
        ];
    }
}
