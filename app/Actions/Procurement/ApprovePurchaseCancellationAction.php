<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\CancellationApproved;
use App\Domain\Procurement\Events\PurchaseRequestCancelled;
use App\Enums\CancellationRequestStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\CancellationRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApprovePurchaseCancellationAction
{
    public function __construct(
        private readonly HandlePurchaseRequestCancellationForDraftPOAction $releaseAllocations
    ) {}

    /**
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function execute(User $actor, CancellationRequest $cancellationRequest, ?string $reason = null): PurchaseRequest
    {
        Gate::forUser($actor)->authorize('cancel', $cancellationRequest->purchaseRequest);

        return DB::transaction(function () use ($actor, $cancellationRequest, $reason) {
            $cancellationRequest = CancellationRequest::where('id', $cancellationRequest->id)->lockForUpdate()->firstOrFail();

            if ($cancellationRequest->status !== CancellationRequestStatus::Pending) {
                throw new \Exception('Cancellation request is not pending.');
            }

            $purchaseRequest = PurchaseRequest::where('id', $cancellationRequest->purchase_request_id)->lockForUpdate()->firstOrFail();

            if ($purchaseRequest->status->isTerminal() || $purchaseRequest->status === PurchaseRequestStatus::PoSent) {
                throw new \Exception('Purchase request cannot be cancelled at this stage.');
            }

            if ($this->hasLinkedSentPurchaseOrder($purchaseRequest)) {
                throw new \Exception('Cannot cancel Purchase Request after Purchase Order has been sent to supplier.');
            }

            $cancellationRequest->update([
                'status' => CancellationRequestStatus::Approved,
                'decided_by' => $actor->id,
                'decision_reason' => $reason,
                'decided_at' => now(),
            ]);

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $cancellationRequest->reason,
            ]);

            $this->releaseAllocations->execute($purchaseRequest);

            CancellationApproved::dispatch($cancellationRequest);
            PurchaseRequestCancelled::dispatch($purchaseRequest, $cancellationRequest->reason);

            return $purchaseRequest;
        });
    }

    private function hasLinkedSentPurchaseOrder(PurchaseRequest $purchaseRequest): bool
    {
        return PurchaseOrder::whereIn('status', [
            PurchaseOrderStatus::SentToSupplier->value,
            PurchaseOrderStatus::GoodsReceived->value,
            PurchaseOrderStatus::Completed->value,
        ])->whereHas('items.allocations.purchaseRequestItem', function ($query) use ($purchaseRequest) {
            $query->where('purchase_request_id', $purchaseRequest->id);
        })->exists();
    }
}
