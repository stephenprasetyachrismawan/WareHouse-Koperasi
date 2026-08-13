<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Events\ReturnRejected;
use App\Enums\ApprovalStatus;
use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * WAITING_APPROVAL -> REJECTED, terminal. No replacement, no disposal, no
 * stock mutation, no Purchase Request creation.
 */
class RejectReturnAction
{
    public function execute(User $actor, ReturnRequest $returnRequest, string $reason): ReturnRequest
    {
        Gate::forUser($actor)->authorize('approve', $returnRequest);

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Rejection reason cannot be empty.');
        }

        return DB::transaction(function () use ($actor, $returnRequest, $reason) {
            $locked = ReturnRequest::where('id', $returnRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ReturnStatus::WaitingApproval) {
                throw new RuntimeException('Only returns waiting for approval can be rejected.');
            }

            $approval = $locked->approvals()->create([
                'uuid' => (string) Str::uuid(),
                'warehouse_id' => $locked->warehouse_id,
                'requested_by' => $locked->submitted_by,
                'approver_id' => $actor->id,
                'status' => ApprovalStatus::Rejected,
                'reason' => $reason,
                'decided_at' => now(),
            ]);

            $locked->update([
                'status' => ReturnStatus::Rejected,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'decision_notes' => $reason,
                'version' => $locked->version + 1,
            ]);

            DB::afterCommit(function () use ($locked, $approval) {
                event(new ReturnRejected($locked->fresh(), $approval));
            });

            return $locked->fresh();
        });
    }
}
