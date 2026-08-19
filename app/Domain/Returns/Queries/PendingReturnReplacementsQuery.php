<?php

namespace App\Domain\Returns\Queries;

use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Staff-facing replacement task list: Returns still waiting on replacement
 * stock, and Returns whose replacement is ready for physical repickup.
 */
class PendingReturnReplacementsQuery
{
    /**
     * @return LengthAwarePaginator<int, ReturnRequest>
     */
    public function execute(int $warehouseId, int $perPage = 10): LengthAwarePaginator
    {
        return ReturnRequest::forWarehouse($warehouseId)
            ->whereIn('status', [ReturnStatus::ReplacementPending->value, ReturnStatus::ReadyForRepickup->value])
            ->with(['items.item', 'cooperativeMembership.user', 'replacementPickup', 'replacementPurchaseRequests'])
            ->orderBy('status')
            ->latest('approved_at')
            ->paginate($perPage);
    }
}
