<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\CancellationRequested;
use App\Enums\CancellationRequestStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\CancellationRequest;
use App\Models\PurchaseRequest;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RequestPurchaseCancellationAction
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $actor, PurchaseRequest $purchaseRequest, string $reason): CancellationRequest
    {
        Gate::forUser($actor)->authorize('requestCancellation', $purchaseRequest);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => ['Cancellation reason is required.'],
            ]);
        }

        if ($purchaseRequest->status->isTerminal() || $purchaseRequest->status === PurchaseRequestStatus::PoSent) {
            throw new Exception('Purchase request cannot be cancelled at this stage.');
        }

        $cancellationRequest = CancellationRequest::create([
            'warehouse_id' => $purchaseRequest->warehouse_id,
            'purchase_request_id' => $purchaseRequest->id,
            'requested_by' => $actor->id,
            'reason' => $reason,
            'status' => CancellationRequestStatus::Pending,
        ]);

        CancellationRequested::dispatch($cancellationRequest);

        return $cancellationRequest;
    }
}
