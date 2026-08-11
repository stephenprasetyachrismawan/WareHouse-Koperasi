<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Events\ReturnSubmitted;
use App\Domain\Returns\Queries\EligibleReturnItemsQuery;
use App\Domain\Returns\ValueObjects\CreateReturnInput;
use App\Enums\PickupRequestStatus;
use App\Enums\ReturnEvidencePurpose;
use App\Enums\ReturnStatus;
use App\Models\PickupRequestItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class CreateReturnAction
{
    public function __construct(
        private readonly EligibleReturnItemsQuery $eligibleReturnItemsQuery,
    ) {}

    public function execute(User $actor, CreateReturnInput $input): ReturnRequest
    {
        Gate::forUser($actor)->authorize('create', ReturnRequest::class);

        $membership = $actor->activeMembership();

        if (! $membership || $membership->warehouse_id !== $input->warehouseId) {
            throw new AuthorizationException('User is not an active member of this warehouse.');
        }

        return DB::transaction(function () use ($actor, $membership, $input) {
            $pickupRequestItem = PickupRequestItem::with('pickupRequest')
                ->where('id', $input->pickupRequestItemId)
                ->lockForUpdate()
                ->firstOrFail();

            $pickupRequest = $pickupRequestItem->pickupRequest;

            if ($pickupRequest->id !== $input->pickupRequestId) {
                throw new RuntimeException('The selected item does not belong to the selected pickup.');
            }

            if ($pickupRequest->warehouse_id !== $input->warehouseId) {
                throw new AuthorizationException('Pickup request does not belong to the active warehouse.');
            }

            if ($pickupRequest->user_id !== $actor->id) {
                throw new AuthorizationException('You may only submit returns for your own pickups.');
            }

            if ($pickupRequest->status !== PickupRequestStatus::Completed) {
                throw new RuntimeException('Only completed pickups are eligible for a return.');
            }

            $eligibleQuantity = $this->eligibleReturnItemsQuery->eligibleQuantity($pickupRequestItem);

            if ($input->returnQuantity > $eligibleQuantity) {
                throw new RuntimeException("Return quantity exceeds eligible quantity ({$eligibleQuantity}).");
            }

            $returnRequest = ReturnRequest::create([
                'warehouse_id' => $input->warehouseId,
                'cooperative_membership_id' => $membership->id,
                'pickup_request_id' => $pickupRequest->id,
                'return_number' => $this->generateReturnNumber(),
                'status' => ReturnStatus::Submitted,
                'reason_code' => $input->reasonCode,
                'reason_notes' => $input->reasonNotes,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'version' => 1,
            ]);

            $returnRequest->items()->create([
                'pickup_request_item_id' => $pickupRequestItem->id,
                'item_id' => $pickupRequestItem->item_id,
                'return_quantity' => $input->returnQuantity,
            ]);

            $returnRequest->evidence()->create([
                'warehouse_id' => $input->warehouseId,
                'purpose' => ReturnEvidencePurpose::ReturnSubmission,
                'uploaded_by' => $actor->id,
                'path' => $input->evidencePath,
                'mime' => $input->evidenceMime,
            ]);

            DB::afterCommit(function () use ($returnRequest) {
                ReturnSubmitted::dispatch($returnRequest->fresh(['items', 'evidence']));
            });

            return $returnRequest->fresh(['items', 'evidence']);
        });
    }

    private function generateReturnNumber(): string
    {
        return sprintf(
            'RET-%s-%s',
            now()->format('Ymd'),
            strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))
        );
    }
}
