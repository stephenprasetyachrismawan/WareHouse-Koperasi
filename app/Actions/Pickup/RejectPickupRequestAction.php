<?php

namespace App\Actions\Pickup;

use App\Domain\Pickup\Events\PickupRequestRejected;
use App\Enums\ApprovalStatus;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RejectPickupRequestAction
{
    public function execute(User $actor, PickupRequest $pickupRequest, string $reason): PickupRequest
    {
        Gate::forUser($actor)->authorize('approve', $pickupRequest);

        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Rejection reason cannot be empty.');
        }

        DB::transaction(function () use ($actor, $pickupRequest, $reason) {
            $pickupRequest = PickupRequest::where('id', $pickupRequest->id)->lockForUpdate()->firstOrFail();

            if (! in_array($pickupRequest->status, [
                PickupRequestStatus::WaitingApproval,
                PickupRequestStatus::Prepared,
                PickupRequestStatus::Backordered,
            ], true)) {
                throw new \DomainException('Pickup Request cannot be rejected in its current status.');
            }

            $approval = $pickupRequest->approvals()->create([
                'warehouse_id' => $pickupRequest->warehouse_id,
                'requested_by' => $pickupRequest->user_id,
                'approver_id' => $actor->id,
                'status' => ApprovalStatus::Rejected,
                'reason' => $reason,
                'decided_at' => now(),
            ]);

            $pickupRequest->update([
                'status' => PickupRequestStatus::Rejected,
            ]);

            DB::afterCommit(function () use ($pickupRequest, $approval) {
                event(new PickupRequestRejected($pickupRequest, $approval));
            });
        });

        return $pickupRequest->refresh();
    }
}
