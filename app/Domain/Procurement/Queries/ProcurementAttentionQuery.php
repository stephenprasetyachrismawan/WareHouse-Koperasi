<?php

namespace App\Domain\Procurement\Queries;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;

/**
 * Kepala Gudang's procurement-wide attention counters — total in-progress
 * requests and sent POs awaiting receipt. Reuses ReceivablePurchaseOrdersQuery
 * rather than re-deriving "sent, not yet received" a second time.
 */
class ProcurementAttentionQuery
{
    public function __construct(
        private readonly ReceivablePurchaseOrdersQuery $receivablePurchaseOrders,
    ) {}

    /** @return array<string, int> */
    public function execute(int $warehouseId, bool $canViewRequests = true, bool $canViewOrders = true): array
    {
        $inProgressStatuses = collect(PurchaseRequestStatus::cases())
            ->filter(fn (PurchaseRequestStatus $status) => $status->isInProgress())
            ->map(fn (PurchaseRequestStatus $status) => $status->value)
            ->all();

        $counts = [];
        if ($canViewRequests) {
            $counts['inProgressCount'] = PurchaseRequest::where('warehouse_id', $warehouseId)
                ->whereIn('status', $inProgressStatuses)
                ->count();
        }
        if ($canViewOrders) {
            $counts['poSentAwaitingReceiptCount'] = $this->receivablePurchaseOrders->execute($warehouseId, 1)->total();
        }

        return $counts;
    }
}
