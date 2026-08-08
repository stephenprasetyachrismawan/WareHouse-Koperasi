<?php

namespace App\Actions\Pickup;

use App\Domain\Pickup\Events\PickupRequestCancelled;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CancelPickupRequestAction
{
    public function execute(User $actor, PickupRequest $pickupRequest, string $reason): PickupRequest
    {
        Gate::forUser($actor)->authorize('cancel', $pickupRequest);

        return DB::transaction(function () use ($pickupRequest, $reason) {
            $lockedRequest = PickupRequest::where('id', $pickupRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->isTerminal()) {
                throw new \RuntimeException('Cannot cancel a terminal pickup request.');
            }

            $lockedRequest->update([
                'status' => PickupRequestStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            DB::afterCommit(function () use ($lockedRequest, $reason) {
                PickupRequestCancelled::dispatch($lockedRequest, $reason);
            });

            return $lockedRequest;
        });
    }
}
