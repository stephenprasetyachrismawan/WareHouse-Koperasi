<?php

namespace App\Domain\Pickup\Queries;

use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;

/**
 * Staff Admin's pickup task counts by stage. Each count is a query
 * category derived from the authoritative PickupRequestStatus, never a
 * separate stored dashboard status.
 */
class PickupTaskSummaryQuery
{
    /**
     * @return array{new: int, backordered: int, toPrepare: int, readyForFulfilment: int}
     */
    public function execute(int $warehouseId): array
    {
        $counts = PickupRequest::forWarehouse($warehouseId)
            ->whereIn('status', [
                PickupRequestStatus::Submitted->value,
                PickupRequestStatus::Backordered->value,
                PickupRequestStatus::Checked->value,
                PickupRequestStatus::ReadyForPickup->value,
            ])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'new' => (int) ($counts[PickupRequestStatus::Submitted->value] ?? 0),
            'backordered' => (int) ($counts[PickupRequestStatus::Backordered->value] ?? 0),
            'toPrepare' => (int) ($counts[PickupRequestStatus::Checked->value] ?? 0),
            'readyForFulfilment' => (int) ($counts[PickupRequestStatus::ReadyForPickup->value] ?? 0),
        ];
    }
}
