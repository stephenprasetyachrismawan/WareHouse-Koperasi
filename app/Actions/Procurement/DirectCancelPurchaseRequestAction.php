<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\PurchaseRequestCancelled;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DirectCancelPurchaseRequestAction
{
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

        $purchaseRequest->update([
            'status' => PurchaseRequestStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        PurchaseRequestCancelled::dispatch($purchaseRequest, $reason);

        return $purchaseRequest;
    }
}
