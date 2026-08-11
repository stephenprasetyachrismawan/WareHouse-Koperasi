<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Events\ReturnApproved;
use App\Domain\Returns\Events\ReturnReplacementPending;
use App\Enums\ApprovalStatus;
use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * WAITING_APPROVAL -> APPROVED -> REPLACEMENT_PENDING, as one atomic
 * transaction: the immutable Approval decision, FR-32 fault attribution,
 * and the disposal record all happen together so a Return can never be left
 * "approved but not yet attributed/disposed". Never touches StockBalance.
 */
class ApproveReturnAction
{
    public function __construct(
        private readonly DetermineReturnFaultAction $determineReturnFault,
        private readonly RecordReturnDisposalAction $recordReturnDisposal,
    ) {}

    public function execute(User $actor, ReturnRequest $returnRequest): ReturnRequest
    {
        Gate::forUser($actor)->authorize('approve', $returnRequest);

        return DB::transaction(function () use ($actor, $returnRequest) {
            $locked = ReturnRequest::with('items')
                ->where('id', $returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== ReturnStatus::WaitingApproval) {
                throw new RuntimeException('Only returns waiting for approval can be approved.');
            }

            $approval = $locked->approvals()->create([
                'warehouse_id' => $locked->warehouse_id,
                'requested_by' => $locked->submitted_by,
                'approver_id' => $actor->id,
                'status' => ApprovalStatus::Approved,
                'decided_at' => now(),
            ]);

            $returnRequestItem = $locked->items->first();
            $attribution = $this->determineReturnFault->execute($returnRequestItem);

            $locked->update([
                'status' => ReturnStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'fault_attribution' => $attribution->attribution,
                'fault_rule_version' => $attribution->ruleVersion,
                'version' => $locked->version + 1,
            ]);

            $disposal = $this->recordReturnDisposal->execute($actor, $returnRequestItem);

            $locked->update([
                'status' => ReturnStatus::ReplacementPending,
                'disposed_at' => $disposal->disposed_at,
                'version' => $locked->version + 1,
            ]);

            DB::afterCommit(function () use ($locked, $approval) {
                event(new ReturnApproved($locked->fresh(), $approval));
                event(new ReturnReplacementPending($locked->fresh()));
            });

            return $locked->fresh();
        });
    }
}
