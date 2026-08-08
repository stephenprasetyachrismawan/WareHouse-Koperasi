<?php

namespace App\Actions\Pickup;

use App\Domain\Pickup\Events\PickupRequestReadyForPickup;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MarkPickupReadyAction
{
    public function execute(User $actor, PickupRequest $pickupRequest): PickupRequest
    {
        Gate::forUser($actor)->authorize('prepare', $pickupRequest);

        return DB::transaction(function () use ($pickupRequest) {
            $lockedRequest = PickupRequest::where('id', $pickupRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedRequest->status, [PickupRequestStatus::Approved, PickupRequestStatus::Prepared], true)) {
                throw new \RuntimeException('Pickup request cannot be marked as ready from its current status.');
            }

            $lockedRequest->update([
                'status' => PickupRequestStatus::ReadyForPickup,
                'ready_at' => now(),
            ]);

            DB::afterCommit(function () use ($lockedRequest) {
                PickupRequestReadyForPickup::dispatch($lockedRequest);
            });

            return $lockedRequest;
        });
    }
}
