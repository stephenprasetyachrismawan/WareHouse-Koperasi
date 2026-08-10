<?php

namespace App\Domain\Procurement\Queries;

use App\Models\PurchaseRequestAllocation;
use App\Models\PurchaseRequestItem;

class PurchaseRequestItemRemainingQuantityQuery
{
    public function execute(PurchaseRequestItem $purchaseRequestItem): int
    {
        $allocated = PurchaseRequestAllocation::where('purchase_request_item_id', $purchaseRequestItem->id)
            ->sum('allocated_quantity');

        return max(0, $purchaseRequestItem->requested_quantity - (int) $allocated);
    }
}
