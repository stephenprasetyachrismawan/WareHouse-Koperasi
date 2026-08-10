<?php

namespace App\Domain\Procurement\Queries;

use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class PurchaseOrderTraceabilityQuery
{
    /**
     * @return Collection<int, array{po_item_id: int, item_name: string, ordered_quantity: int, allocations: Collection}>
     */
    public function execute(PurchaseOrder $purchaseOrder): Collection
    {
        return $purchaseOrder->items()
            ->with([
                'item',
                'allocations.purchaseRequestItem.purchaseRequest',
                'allocations.allocator',
            ])
            ->get()
            ->map(function ($purchaseOrderItem) {
                return [
                    'po_item_id' => $purchaseOrderItem->id,
                    'item_name' => $purchaseOrderItem->item->name,
                    'ordered_quantity' => $purchaseOrderItem->ordered_quantity,
                    'allocations' => $purchaseOrderItem->allocations->map(fn ($allocation) => [
                        'purchase_request_number' => $allocation->purchaseRequestItem->purchaseRequest->request_number,
                        'source' => $allocation->purchaseRequestItem->purchaseRequest->source,
                        'allocated_quantity' => $allocation->allocated_quantity,
                        'allocated_by' => $allocation->allocator->name ?? null,
                    ]),
                ];
            });
    }
}
