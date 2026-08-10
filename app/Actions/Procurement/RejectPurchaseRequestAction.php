<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\PurchaseRequestRejected;
use App\Enums\ApprovalStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class RejectPurchaseRequestAction
{
    public function execute(User $actor, PurchaseRequest $purchaseRequest, string $reason): PurchaseRequest
    {
        Gate::forUser($actor)->authorize('reject', $purchaseRequest);

        if (trim($reason) === '') {
            throw new \Exception('Reason cannot be empty.');
        }

        return DB::transaction(function () use ($actor, $purchaseRequest, $reason) {
            $purchaseRequest = PurchaseRequest::lockForUpdate()->findOrFail($purchaseRequest->id);

            if ($purchaseRequest->status !== PurchaseRequestStatus::WaitingApproval) {
                throw new \Exception('Only WaitingApproval purchase requests can be rejected.');
            }

            $purchaseRequest->approvals()->create([
                'uuid' => (string) Str::uuid(),
                'warehouse_id' => $purchaseRequest->warehouse_id,
                'requested_by' => $purchaseRequest->created_by,
                'approver_id' => $actor->id,
                'status' => ApprovalStatus::Rejected,
                'reason' => $reason,
                'decided_at' => now(),
            ]);

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::Rejected,
                'rejected_at' => now(),
            ]);

            event(new PurchaseRequestRejected($purchaseRequest, $actor, $reason));

            return $purchaseRequest;
        });
    }
}
