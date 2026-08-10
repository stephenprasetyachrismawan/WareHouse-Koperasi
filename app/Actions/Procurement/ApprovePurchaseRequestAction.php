<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\PurchaseRequestApproved;
use App\Enums\ApprovalStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ApprovePurchaseRequestAction
{
    public function execute(User $actor, PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        Gate::forUser($actor)->authorize('approve', $purchaseRequest);

        return DB::transaction(function () use ($actor, $purchaseRequest) {
            $purchaseRequest = PurchaseRequest::lockForUpdate()->findOrFail($purchaseRequest->id);

            if ($purchaseRequest->status !== PurchaseRequestStatus::WaitingApproval) {
                throw new \Exception('Only WaitingApproval purchase requests can be approved.');
            }

            $purchaseRequest->approvals()->create([
                'uuid' => (string) Str::uuid(),
                'warehouse_id' => $purchaseRequest->warehouse_id,
                'requested_by' => $purchaseRequest->created_by,
                'approver_id' => $actor->id,
                'status' => ApprovalStatus::Approved,
                'decided_at' => now(),
            ]);

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::Approved,
                'approved_at' => now(),
            ]);

            event(new PurchaseRequestApproved($purchaseRequest, $actor));

            return $purchaseRequest;
        });
    }
}
