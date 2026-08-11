<?php

namespace App\Actions\Returns;

use App\Domain\Returns\ValueObjects\ReplacementAvailability;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;

/**
 * Pure read: does the warehouse currently hold enough stock to fulfil this
 * Return's replacement quantity? Never mutates StockBalance. Always reads
 * the authoritative current balance — never trusts a client-provided flag.
 */
class CheckReplacementAvailabilityAction
{
    public function execute(ReturnRequestItem $returnRequestItem): ReplacementAvailability
    {
        $warehouseId = $returnRequestItem->returnRequest->warehouse_id;
        $required = $returnRequestItem->return_quantity;

        $balance = StockBalance::where('warehouse_id', $warehouseId)
            ->where('item_id', $returnRequestItem->item_id)
            ->first();

        $available = max(0, $balance?->quantity ?? 0);

        return new ReplacementAvailability(
            isAvailable: $available >= $required,
            requiredQuantity: $required,
            availableQuantity: $available,
            shortfallQuantity: max(0, $required - $available),
        );
    }
}
