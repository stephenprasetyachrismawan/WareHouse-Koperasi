<?php

namespace App\Domain\Procurement\Queries;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Collection;

/**
 * FR-38: total in-progress Purchase Request quantity per Item, across every
 * source (CRITICAL_STOCK, COOPERATIVE_BACKORDER, MANUAL_STAFF,
 * RETURN_REPLACEMENT, and any future source) — never just one source.
 *
 * "In-progress" is never a stored status; it is derived from
 * PurchaseRequestStatus::isInProgress(), the single centralized definition
 * also used by reports, so this widget can never drift from what the
 * status enum itself considers non-terminal.
 *
 * Counts PurchaseRequestItem.requested_quantity directly — never PO or
 * allocation quantities — so a request line is counted exactly once
 * regardless of how far it has progressed through grouping/allocation/PO.
 */
class PurchaseRequestInProgressByItemQuery
{
    /**
     * @return Collection<int, object{item_id: int, item_name: string, total_quantity: int}>
     */
    public function execute(int $warehouseId): Collection
    {
        $inProgressStatuses = collect(PurchaseRequestStatus::cases())
            ->filter(fn (PurchaseRequestStatus $status) => $status->isInProgress())
            ->map(fn (PurchaseRequestStatus $status) => $status->value)
            ->all();

        return PurchaseRequestItem::query()
            ->selectRaw('item_id, SUM(requested_quantity) as total_quantity')
            ->whereHas('purchaseRequest', function ($query) use ($warehouseId, $inProgressStatuses) {
                $query->where('warehouse_id', $warehouseId)
                    ->whereIn('status', $inProgressStatuses);
            })
            ->with('item:id,name,unit')
            ->groupBy('item_id')
            ->orderByDesc('total_quantity')
            ->get()
            ->map(fn (PurchaseRequestItem $row) => (object) [
                'item_id' => $row->item_id,
                'item_name' => $row->item->name,
                'total_quantity' => (int) $row->total_quantity,
            ]);
    }
}
