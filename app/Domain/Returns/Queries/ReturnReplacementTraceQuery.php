<?php

namespace App\Domain\Returns\Queries;

use App\Models\GoodsReceiptItem;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;

/**
 * Traces a just-accepted receiving line back to the RETURN_REPLACEMENT
 * Purchase Request (if any) that funded it, via the real persisted chain:
 * GoodsReceiptItem -> PurchaseOrderItem -> Allocation -> PurchaseRequestItem
 * -> PurchaseRequest -> ReturnRequest. Never matches on Item alone.
 */
class ReturnReplacementTraceQuery
{
    public function findLinkedReturnRequest(GoodsReceiptItem $goodsReceiptItem): ?ReturnRequest
    {
        $purchaseRequestIds = $goodsReceiptItem->purchaseOrderItem
            ->allocations()
            ->with('purchaseRequestItem.purchaseRequest')
            ->get()
            ->pluck('purchaseRequestItem.purchaseRequest.id')
            ->filter()
            ->unique();

        if ($purchaseRequestIds->isEmpty()) {
            return null;
        }

        $purchaseRequest = PurchaseRequest::whereIn('id', $purchaseRequestIds)
            ->whereNotNull('return_request_id')
            ->first();

        return $purchaseRequest?->returnRequest;
    }
}
