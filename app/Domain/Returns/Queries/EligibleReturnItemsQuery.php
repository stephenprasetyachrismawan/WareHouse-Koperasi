<?php

namespace App\Domain\Returns\Queries;

use App\Enums\PickupRequestStatus;
use App\Enums\ReturnStatus;
use App\Models\PickupRequestItem;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Completed Pickup lines a Koperasi user may still submit a Return against.
 * A line's eligible quantity is its fulfilled quantity minus everything
 * already committed to a non-rejected Return, so double-returning the same
 * stock is impossible regardless of how many Return requests exist.
 */
class EligibleReturnItemsQuery
{
    /**
     * @return Collection<int, PickupRequestItem>
     */
    public function execute(User $koperasiUser, int $warehouseId): Collection
    {
        return PickupRequestItem::query()
            ->where('fulfilled_quantity', '>', 0)
            ->whereHas('pickupRequest', function ($query) use ($koperasiUser, $warehouseId) {
                $query->where('warehouse_id', $warehouseId)
                    ->where('user_id', $koperasiUser->id)
                    ->where('status', PickupRequestStatus::Completed->value);
            })
            ->with(['item', 'pickupRequest'])
            ->get()
            ->map(function (PickupRequestItem $line) {
                $line->eligible_quantity = $this->eligibleQuantity($line);

                return $line;
            })
            ->filter(fn (PickupRequestItem $line) => $line->eligible_quantity > 0)
            ->values();
    }

    public function eligibleQuantity(PickupRequestItem $line): int
    {
        $alreadyReturned = $line->returnRequestItems()
            ->whereHas('returnRequest', function ($query) {
                $query->where('status', '!=', ReturnStatus::Rejected->value);
            })
            ->sum('return_quantity');

        return max(0, $line->fulfilled_quantity - (int) $alreadyReturned);
    }
}
