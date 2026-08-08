<?php

namespace App\Actions\Pickup;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Domain\Pickup\Events\PickupRequestCompleted;
use App\Enums\MovementType;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FulfillPickupAction
{
    public function __construct(private RecordStockMovementAction $recordStockMovementAction) {}

    public function execute(User $actor, PickupRequest $pickupRequest): PickupRequest
    {
        Gate::forUser($actor)->authorize('fulfill', $pickupRequest);

        return DB::transaction(function () use ($actor, $pickupRequest) {
            $lockedRequest = PickupRequest::with('items')
                ->where('id', $pickupRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedRequest->status, [PickupRequestStatus::ReadyForPickup, PickupRequestStatus::Approved], true)) {
                if ($lockedRequest->status === PickupRequestStatus::Completed) {
                    return $lockedRequest;
                }
                throw new \RuntimeException('Pickup request cannot be fulfilled from its current status.');
            }

            foreach ($lockedRequest->items as $line) {
                if ($line->requested_quantity > 0) {
                    $input = new StockMovementInput(
                        warehouseId: $lockedRequest->warehouse_id,
                        itemId: $line->item_id,
                        movementType: MovementType::PickupIssue,
                        quantity: $line->requested_quantity,
                        performedBy: $actor->id,
                        idempotencyKey: "pickup-fulfill-{$lockedRequest->id}-{$line->id}",
                        reason: "Pengambilan Koperasi #{$lockedRequest->request_number}",
                        sourceType: PickupRequest::class,
                        sourceId: $lockedRequest->id
                    );

                    $this->recordStockMovementAction->execute($input);

                    $line->update([
                        'fulfilled_quantity' => $line->requested_quantity,
                    ]);
                }
            }

            $lockedRequest->update([
                'status' => PickupRequestStatus::Completed,
                'completed_at' => now(),
            ]);

            DB::afterCommit(function () use ($lockedRequest) {
                PickupRequestCompleted::dispatch($lockedRequest);
            });

            return $lockedRequest;
        });
    }
}
