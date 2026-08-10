<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\PurchaseRequestCancelled;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DirectCancelPurchaseRequestAction
{
    public function __construct(
        private readonly HandlePurchaseRequestCancellationForDraftPOAction $releaseAllocations
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws \Exception
     */
    public function execute(User $actor, PurchaseRequest $purchaseRequest, string $reason): PurchaseRequest
    {
        Gate::forUser($actor)->authorize('cancel', $purchaseRequest);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => ['Cancellation reason is required.'],
            ]);
        }

        if ($purchaseRequest->status->isTerminal() || $purchaseRequest->status === PurchaseRequestStatus::PoSent) {
            throw new \Exception('Purchase request cannot be cancelled at this stage.');
        }

        if ($this->hasLinkedSentPurchaseOrder($purchaseRequest)) {
            throw new \Exception('Cannot cancel Purchase Request after Purchase Order has been sent to supplier.');
        }

        return DB::transaction(function () use ($purchaseRequest, $reason) {
            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->releaseAllocations->execute($purchaseRequest);

            PurchaseRequestCancelled::dispatch($purchaseRequest, $reason);

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
