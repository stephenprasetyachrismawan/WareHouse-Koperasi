<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Queries\PurchaseRequestItemRemainingQuantityQuery;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequestGroup;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatePurchaseRequestGroupAction
{
    public function __construct(
        private readonly PurchaseRequestItemRemainingQuantityQuery $remainingQuantityQuery
    ) {}

    public function execute(User $actor, CreateGroupInput $input): PurchaseRequestGroup
    {
        Gate::forUser($actor)->authorize('create', PurchaseRequestGroup::class);

        return DB::transaction(function () use ($actor, $input) {
            $group = PurchaseRequestGroup::create([
                'warehouse_id' => $input->warehouseId,
                'group_number' => $this->generateGroupNumber($input->warehouseId),
                'created_by' => $actor->id,
                'notes' => $input->notes,
            ]);

            foreach ($input->allocations as $allocationInput) {
                $purchaseRequestItem = PurchaseRequestItem::with('purchaseRequest')
                    ->lockForUpdate()
                    ->findOrFail($allocationInput->purchaseRequestItemId);

                $purchaseRequest = $purchaseRequestItem->purchaseRequest;

                if ($purchaseRequest->warehouse_id !== $input->warehouseId) {
                    throw new Exception('Purchase request item does not belong to the active warehouse.');
                }

                if ($purchaseRequest->status !== PurchaseRequestStatus::Approved) {
                    throw new Exception('Only APPROVED purchase requests can be allocated.');
                }

                $remaining = $this->remainingQuantityQuery->execute($purchaseRequestItem);

                if ($allocationInput->quantity > $remaining) {
                    throw new Exception("Allocated quantity ({$allocationInput->quantity}) exceeds remaining allocatable quantity ({$remaining}) for purchase request item #{$purchaseRequestItem->id}.");
                }

                $group->allocations()->create([
                    'warehouse_id' => $input->warehouseId,
                    'purchase_request_item_id' => $purchaseRequestItem->id,
                    'allocated_quantity' => $allocationInput->quantity,
                    'allocated_by' => $actor->id,
                ]);
            }

            return $group->load('allocations');
        });
    }

    private function generateGroupNumber(int $warehouseId): string
    {
        $date = now()->format('Ymd');

        Warehouse::query()->whereKey($warehouseId)->lockForUpdate()->firstOrFail();

        $sequence = PurchaseRequestGroup::forWarehouse($warehouseId)
            ->where('group_number', 'like', "PRG-{$date}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('PRG-%s-%04d', $date, $sequence);
    }
}
