<?php

namespace App\Domain\Approvals\Queries;

use App\Enums\CancellationRequestStatus;
use App\Enums\PickupRequestStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\ReturnStatus;
use App\Models\CancellationRequest;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\WarehouseMembership;

/**
 * Approval records only exist once a decision has been made (see
 * Approval model) — "pending" is never a queryable Approval row, it is
 * derived from the source entity's own WAITING_APPROVAL/PENDING status.
 * Kepala Gudang is the only actor who sees all four counts together.
 */
class PendingApprovalsSummaryQuery
{
    /**
     * @return array{purchaseRequests: int, pickupRequests: int, returns: int, cancellations: int}
     */
    /** @return array<string, int> */
    public function execute(int $warehouseId, ?WarehouseMembership $membership = null): array
    {
        $can = static fn (string $permission): bool => $membership === null || $membership->hasPermission($permission);
        $counts = [];

        if ($can('purchase_request.approve')) {
            $counts['purchaseRequests'] = PurchaseRequest::where('warehouse_id', $warehouseId)
                ->where('status', PurchaseRequestStatus::WaitingApproval->value)
                ->count();
        }
        if ($can('pickup_request.approve')) {
            $counts['pickupRequests'] = PickupRequest::forWarehouse($warehouseId)
                ->where('status', PickupRequestStatus::WaitingApproval->value)
                ->count();
        }
        if ($can('returns.approve')) {
            $counts['returns'] = ReturnRequest::forWarehouse($warehouseId)
                ->where('status', ReturnStatus::WaitingApproval->value)
                ->count();
        }
        if ($can('purchase_request.cancel')) {
            $counts['cancellations'] = CancellationRequest::where('warehouse_id', $warehouseId)
                ->where('status', CancellationRequestStatus::Pending->value)
                ->count();
        }

        return $counts;
    }
}
