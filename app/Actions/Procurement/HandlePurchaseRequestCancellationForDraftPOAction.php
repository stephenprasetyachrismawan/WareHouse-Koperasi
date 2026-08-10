<?php

namespace App\Actions\Procurement;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;

class HandlePurchaseRequestCancellationForDraftPOAction
{
    /**
     * Release every allocation tied to the given (now cancelled) Purchase Request,
     * shrinking or removing the affected DRAFT Purchase Order lines so grouping
     * invariants stay consistent.
     */
    public function execute(PurchaseRequest $purchaseRequest): void
    {
        $itemIds = $purchaseRequest->items()->pluck('id');

        $allocations = PurchaseRequestAllocation::whereIn('purchase_request_item_id', $itemIds)
            ->with('purchaseOrderItem')
            ->lockForUpdate()
            ->get();

        $affectedPurchaseOrderItems = $allocations->pluck('purchaseOrderItem')->filter()->unique('id');

        foreach ($allocations as $allocation) {
            $allocation->delete();
        }

        foreach ($affectedPurchaseOrderItems as $purchaseOrderItem) {
            $remaining = (int) $purchaseOrderItem->allocations()->sum('allocated_quantity');

            if ($remaining <= 0) {
                $purchaseOrderItem->delete();
            } else {
                $purchaseOrderItem->update(['ordered_quantity' => $remaining]);
            }
        }
    }
}
