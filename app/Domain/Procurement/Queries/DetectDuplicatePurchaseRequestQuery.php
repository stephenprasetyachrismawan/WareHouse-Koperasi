<?php

namespace App\Domain\Procurement\Queries;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;

class DetectDuplicatePurchaseRequestQuery
{
    public function __construct(
        private readonly InProgressPurchaseRequestsForItem $inProgressQuery
    ) {}

    public function execute(int $warehouseId, int $itemId): array
    {
        $inProgressQty = $this->inProgressQuery->execute($warehouseId, $itemId);

        $candidates = PurchaseRequest::where('warehouse_id', $warehouseId)
            ->whereNotIn('status', [
                PurchaseRequestStatus::Completed,
                PurchaseRequestStatus::Rejected,
                PurchaseRequestStatus::Cancelled,
            ])
            ->whereHas('items', function ($query) use ($itemId) {
                $query->where('item_id', $itemId);
            })
            ->get();

        return [
            'is_duplicate' => $inProgressQty > 0,
            'in_progress_qty' => $inProgressQty,
            'candidates' => $candidates,
        ];
    }
}
