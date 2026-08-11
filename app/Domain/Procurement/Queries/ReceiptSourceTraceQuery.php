<?php

namespace App\Domain\Procurement\Queries;

use App\Models\GoodsReceiptItem;
use Illuminate\Support\Collection;

class ReceiptSourceTraceQuery
{
    /**
     * @return Collection<int, array{purchase_request_number: string, source: mixed, allocated_quantity: int}>
     */
    public function execute(GoodsReceiptItem $goodsReceiptItem): Collection
    {
        return $goodsReceiptItem->purchaseOrderItem
            ->allocations()
            ->with('purchaseRequestItem.purchaseRequest')
            ->get()
            ->map(fn ($allocation) => [
                'purchase_request_number' => $allocation->purchaseRequestItem->purchaseRequest->request_number,
                'source' => $allocation->purchaseRequestItem->purchaseRequest->source,
                'allocated_quantity' => $allocation->allocated_quantity,
            ]);
    }
}
