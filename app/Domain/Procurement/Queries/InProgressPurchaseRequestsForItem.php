<?php

namespace App\Domain\Procurement\Queries;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequestItem;

class InProgressPurchaseRequestsForItem
{
    public function execute(int $warehouseId, int $itemId): int
    {
        return (int) PurchaseRequestItem::where('item_id', $itemId)
            ->whereHas('purchaseRequest', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId)
                    ->whereNotIn('status', [
                        PurchaseRequestStatus::Completed,
                        PurchaseRequestStatus::Rejected,
                        PurchaseRequestStatus::Cancelled,
                    ]);
            })
            ->sum('requested_quantity');
    }
}
