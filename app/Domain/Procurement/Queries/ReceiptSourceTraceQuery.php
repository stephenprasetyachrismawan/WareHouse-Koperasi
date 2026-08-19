<?php

namespace App\Domain\Procurement\Queries;

use App\Models\GoodsReceiptItem;
use App\Models\PurchaseRequestAllocation;

class ReceiptSourceTraceQuery
{
    /**
     * @return array<int, array{purchase_request_number: string, source: mixed, allocated_quantity: int}>
     */
    public function execute(GoodsReceiptItem $goodsReceiptItem): array
    {
        return $goodsReceiptItem->purchaseOrderItem
            ->allocations()
            ->with('purchaseRequestItem.purchaseRequest')
            ->get()
            ->map(fn (PurchaseRequestAllocation $allocation): array => [
                'purchase_request_number' => $allocation->purchaseRequestItem->purchaseRequest->request_number,
                'source' => $allocation->purchaseRequestItem->purchaseRequest->source,
                'allocated_quantity' => $allocation->allocated_quantity,
            ])
            ->all();
    }
}
