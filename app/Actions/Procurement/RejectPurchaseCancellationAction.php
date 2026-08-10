<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\CancellationRejected;
use App\Enums\CancellationRequestStatus;
use App\Models\CancellationRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectPurchaseCancellationAction
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws \Exception
     */
    public function execute(User $actor, CancellationRequest $cancellationRequest, string $reason): CancellationRequest
    {
        Gate::forUser($actor)->authorize('cancel', $cancellationRequest->purchaseRequest);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => ['Rejection reason is required.'],
            ]);
        }

        if ($cancellationRequest->status !== CancellationRequestStatus::Pending) {
            throw new \Exception('Cancellation request is not pending.');
        }

        $cancellationRequest->update([
            'status' => CancellationRequestStatus::Rejected,
            'decided_by' => $actor->id,
            'decision_reason' => $reason,
            'decided_at' => now(),
        ]);

        CancellationRejected::dispatch($cancellationRequest);

        return $cancellationRequest;
    }
}
