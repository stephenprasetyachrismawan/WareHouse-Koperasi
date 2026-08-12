<?php

namespace App\Domain\Procurement\Queries;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\WarehouseMembership;
use Illuminate\Support\Collection;

class PurchasingAttentionQuery
{
    public function __construct(
        private readonly ApprovedAllocatablePurchaseRequestsQuery $groupingCandidates,
        private readonly ReceivablePurchaseOrdersQuery $receivablePurchaseOrders,
    ) {}

    /** @return array<string, int> */
    public function execute(int $warehouseId, ?WarehouseMembership $membership = null): array
    {
        $canRequest = $membership === null || $membership->hasPermission('purchase_request.viewAny');
        $canGroup = $membership === null || $membership->hasPermission('purchase_group.viewAny');
        $canOrders = $membership === null || $membership->hasPermission('purchase_order.viewAny');
        $counts = [];

        if ($canRequest) {
            $counts['approvedAwaitingProcurementCount'] = PurchaseRequest::where('warehouse_id', $warehouseId)
                ->where('status', PurchaseRequestStatus::Approved->value)
                ->count();
        }
        if ($canGroup) {
            $counts['groupingCandidateCount'] = $this->groupingCandidates->execute($warehouseId)->count();
        }
        if ($canOrders) {
            $counts['draftPoCount'] = PurchaseOrder::where('warehouse_id', $warehouseId)
                ->where('status', PurchaseOrderStatus::Draft->value)
                ->count();
            $counts['sentPoAwaitingReceiptCount'] = $this->receivablePurchaseOrders->execute($warehouseId, 1)->total();
        }

        return $counts;
    }

    /**
     * @return Collection<int, GoodsReceipt>
     */
    public function recentGoodsReceipts(int $warehouseId, int $limit = 5, bool $allowed = true): Collection
    {
        if (! $allowed) {
            return collect();
        }

        return GoodsReceipt::forWarehouse($warehouseId)
            ->with(['purchaseOrder.supplier'])
            ->latest('received_at')
            ->limit($limit)
            ->get();
    }
}
