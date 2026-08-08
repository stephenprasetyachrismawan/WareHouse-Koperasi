<?php

namespace App\Actions\Pickup;

use App\Domain\Pickup\Events\PickupRequestApproved;
use App\Enums\ApprovalStatus;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApprovePickupRequestAction
{
    public function execute(User $actor, PickupRequest $pickupRequest): PickupRequest
    {
        Gate::forUser($actor)->authorize('approve', $pickupRequest);

        DB::transaction(function () use ($actor, $pickupRequest) {
            $pickupRequest = PickupRequest::where('id', $pickupRequest->id)->lockForUpdate()->firstOrFail();

            if (! in_array($pickupRequest->status, [
                PickupRequestStatus::WaitingApproval,
                PickupRequestStatus::Prepared,
                PickupRequestStatus::Backordered,
            ], true)) {
                throw new \DomainException('Pickup Request cannot be approved in its current status.');
            }

            $approval = $pickupRequest->approvals()->create([
                'warehouse_id' => $pickupRequest->warehouse_id,
                'requested_by' => $pickupRequest->user_id,
                'approver_id' => $actor->id,
                'status' => ApprovalStatus::Approved,
                'decided_at' => now(),
            ]);

            $pickupRequest->update([
                'status' => PickupRequestStatus::Approved,
                'approved_at' => now(),
            ]);

            DB::afterCommit(function () use ($pickupRequest, $approval) {
                event(new PickupRequestApproved($pickupRequest, $approval));
            });
        });

        return $pickupRequest->refresh();
    }
}
