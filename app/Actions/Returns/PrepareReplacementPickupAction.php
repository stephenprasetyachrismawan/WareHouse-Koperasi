<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Events\ReturnReadyForRepickup;
use App\Enums\ApprovalStatus;
use App\Enums\PickupRequestSource;
use App\Enums\PickupRequestStatus;
use App\Enums\ReturnStatus;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;

/**
 * The orchestrator for REPLACEMENT_PENDING: checks authoritative stock and
 * either prepares a ready-to-collect replacement Pickup, or ensures a
 * RETURN_REPLACEMENT Purchase Request exists and leaves the Return pending.
 *
 * No separate human approval is invented for the replacement Pickup — the
 * Return's own Kepala Gudang approval is treated as authoritative, recorded
 * via an AUTO_APPROVED Approval row (the same pattern ARCHITECTURE.md
 * documents for direct/self-approved requests), so audit isn't bypassed.
 *
 * System-invoked (event listener or a manual staff recheck); has no Gate
 * check of its own, mirroring CreateBackorderPurchaseRequestAction/its
 * listener. A caller-facing UI trigger authorizes before calling this.
 *
 * Idempotent: a no-op once the Return has moved past REPLACEMENT_PENDING.
 */
class PrepareReplacementPickupAction
{
    public function __construct(
        private readonly CheckReplacementAvailabilityAction $checkAvailability,
        private readonly CreateReturnReplacementPurchaseRequestAction $createFallbackPurchaseRequest,
    ) {}

    public function execute(ReturnRequest $returnRequest): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequest) {
            $locked = ReturnRequest::with(['items', 'cooperativeMembership.user'])
                ->where('id', $returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== ReturnStatus::ReplacementPending) {
                return $locked;
            }

            $returnRequestItem = $locked->items->first();
            $availability = $this->checkAvailability->execute($returnRequestItem);

            if (! $availability->isAvailable) {
                $this->createFallbackPurchaseRequest->execute($returnRequestItem, $availability->shortfallQuantity);

                return $locked;
            }

            $koperasiUser = $locked->cooperativeMembership->user;

            $pickupRequest = PickupRequest::create([
                'warehouse_id' => $locked->warehouse_id,
                'request_number' => $this->generatePickupNumber(),
                'user_id' => $koperasiUser->id,
                'source' => PickupRequestSource::ReturnReplacement,
                'status' => PickupRequestStatus::Draft,
                'notes' => "Penggantian retur {$locked->return_number}",
            ]);

            $pickupRequest->items()->create([
                'item_id' => $returnRequestItem->item_id,
                'requested_quantity' => $returnRequestItem->return_quantity,
            ]);

            $pickupRequest->approvals()->create([
                'warehouse_id' => $locked->warehouse_id,
                'requested_by' => $koperasiUser->id,
                'approver_id' => $locked->approved_by,
                'status' => ApprovalStatus::AutoApproved,
                'reason' => "Otomatis disetujui berdasarkan persetujuan retur {$locked->return_number}",
                'decided_at' => now(),
            ]);

            $pickupRequest->update([
                'status' => PickupRequestStatus::Approved,
                'approved_at' => now(),
            ]);

            $pickupRequest->update([
                'status' => PickupRequestStatus::ReadyForPickup,
                'ready_at' => now(),
            ]);

            $locked->update([
                'status' => ReturnStatus::ReadyForRepickup,
                'replacement_pickup_request_id' => $pickupRequest->id,
                'version' => $locked->version + 1,
            ]);

            DB::afterCommit(function () use ($locked) {
                ReturnReadyForRepickup::dispatch($locked->fresh());
            });

            return $locked->fresh();
        });
    }

    private function generatePickupNumber(): string
    {
        return sprintf(
            'REQ-%s-%s',
            now()->format('Ymd'),
            strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))
        );
    }
}
