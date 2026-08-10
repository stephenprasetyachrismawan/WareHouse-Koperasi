<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\PurchaseRequestSubmitted;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubmitPurchaseForApprovalAction
{
    public function execute(User $actor, PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        Gate::forUser($actor)->authorize('create', PurchaseRequest::class);

        return DB::transaction(function () use ($actor, $purchaseRequest) {
            $purchaseRequest = PurchaseRequest::lockForUpdate()->findOrFail($purchaseRequest->id);

            if ($purchaseRequest->status !== PurchaseRequestStatus::Draft) {
                throw new \Exception('Only Draft purchase requests can be submitted.');
            }

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::WaitingApproval,
                'submitted_at' => now(),
            ]);

            event(new PurchaseRequestSubmitted($purchaseRequest, $actor));

            return $purchaseRequest;
        });
    }
}
