<?php

namespace App\Actions\Returns;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Domain\Returns\Events\ReturnCompleted;
use App\Enums\MovementType;
use App\Enums\PickupRequestStatus;
use App\Enums\ReturnStatus;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Physical repickup completion: exactly one authoritative stock-out via
 * RecordStockMovementAction (MovementType::ReplacementIssue), then the
 * Return transitions to COMPLETED. Reuses PickupRequestPolicy::fulfill —
 * the same ability Staff Admin already holds for ordinary Pickup fulfilment.
 */
class CompleteReplacementPickupAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordStockMovement,
    ) {}

    public function execute(User $actor, ReturnRequest $returnRequest): ReturnRequest
    {
        if ($returnRequest->status !== ReturnStatus::Completed
            && $returnRequest->status !== ReturnStatus::ReadyForRepickup) {
            throw new RuntimeException('Only returns ready for repickup can be completed.');
        }

        $pickupRequest = PickupRequest::findOrFail($returnRequest->replacement_pickup_request_id);

        Gate::forUser($actor)->authorize('fulfill', $pickupRequest);

        return DB::transaction(function () use ($actor, $returnRequest) {
            $locked = ReturnRequest::with('items')
                ->where('id', $returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ReturnStatus::Completed) {
                return $locked;
            }

            if ($locked->status !== ReturnStatus::ReadyForRepickup) {
                throw new RuntimeException('Only returns ready for repickup can be completed.');
            }

            $lockedPickup = PickupRequest::with('items')
                ->where('id', $locked->replacement_pickup_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPickup->status !== PickupRequestStatus::ReadyForPickup) {
                throw new RuntimeException('Replacement pickup is not ready for repickup.');
            }

            $returnRequestItem = $locked->items->first();
            $pickupItem = $lockedPickup->items->first();

            $this->recordStockMovement->execute(new StockMovementInput(
                warehouseId: $locked->warehouse_id,
                itemId: $returnRequestItem->item_id,
                movementType: MovementType::ReplacementIssue,
                quantity: $returnRequestItem->return_quantity,
                performedBy: $actor->id,
                idempotencyKey: "warehouse:{$locked->warehouse_id}:return:{$locked->id}:replacement-item:{$returnRequestItem->item_id}:issue",
                reason: "Penggantian retur {$locked->return_number}",
                sourceType: ReturnRequest::class,
                sourceId: $locked->id,
            ));

            $pickupItem->update(['fulfilled_quantity' => $pickupItem->requested_quantity]);

            $lockedPickup->update([
                'status' => PickupRequestStatus::Completed,
                'completed_at' => now(),
            ]);

            $locked->update([
                'status' => ReturnStatus::Completed,
                'version' => $locked->version + 1,
            ]);

            DB::afterCommit(function () use ($locked) {
                ReturnCompleted::dispatch($locked->fresh());
            });

            return $locked->fresh();
        });
    }
}
