<?php

namespace App\Domain\Procurement\Queries;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Collection;

class ApprovedAllocatablePurchaseRequestsQuery
{
    /**
     * @return Collection<int, PurchaseRequestItem>
     */
    public function execute(int $warehouseId): Collection
    {
        return PurchaseRequestItem::query()
            ->whereHas('purchaseRequest', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId)
                    ->where('status', PurchaseRequestStatus::Approved->value);
            })
            ->with(['purchaseRequest', 'item'])
            ->withSum('allocations as allocated_quantity_sum', 'allocated_quantity')
            ->get()
            ->map(function (PurchaseRequestItem $item) {
                $item->remaining_quantity = max(0, $item->requested_quantity - (int) $item->allocated_quantity_sum);

                return $item;
            })
            ->filter(fn (PurchaseRequestItem $item) => $item->remaining_quantity > 0)
            ->values();
    }
}
