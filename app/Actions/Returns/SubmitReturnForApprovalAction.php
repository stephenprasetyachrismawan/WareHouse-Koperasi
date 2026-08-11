<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Events\ReturnSubmittedForApproval;
use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Explicit ADMIN_VERIFIED -> WAITING_APPROVAL transition, kept separate from
 * VerifyReturnAction so the domain state machine stays testable even though
 * the UI may call both actions back-to-back in a single staff interaction.
 *
 * This does not create an Approval row: mirroring the existing Purchase
 * Request approval-inbox precedent, the ReturnRequest.status itself is the
 * queue marker. Phase 5.2's decision action creates the Approval row at the
 * moment the Head Gudang actually decides, not before.
 */
class SubmitReturnForApprovalAction
{
    public function execute(User $actor, ReturnRequest $returnRequest, int $expectedVersion): ReturnRequest
    {
        Gate::forUser($actor)->authorize('submitForApproval', $returnRequest);

        return DB::transaction(function () use ($returnRequest, $expectedVersion) {
            $locked = ReturnRequest::with('items')
                ->where('id', $returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->version !== $expectedVersion) {
                throw new RuntimeException('This return has already been updated by another action. Please reload and try again.');
            }

            if ($locked->status !== ReturnStatus::AdminVerified) {
                throw new RuntimeException('Only admin-verified returns can be submitted for approval.');
            }

            if ($locked->items->contains(fn ($item) => ! $item->barcode_verified)) {
                throw new RuntimeException('All return items must complete barcode verification before submitting for approval.');
            }

            $locked->update([
                'status' => ReturnStatus::WaitingApproval,
                'waiting_approval_at' => now(),
                'version' => $locked->version + 1,
            ]);

            DB::afterCommit(function () use ($locked) {
                ReturnSubmittedForApproval::dispatch($locked->fresh());
            });

            return $locked->fresh();
        });
    }
}
