<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\PurchaseOrderCreated;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;
use App\Models\PurchaseRequestGroup;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatePurchaseOrderAction
{
    public function execute(User $actor, CreatePurchaseOrderInput $input): PurchaseOrder
    {
        Gate::forUser($actor)->authorize('create', PurchaseOrder::class);

        return DB::transaction(function () use ($actor, $input) {
            $group = PurchaseRequestGroup::forWarehouse($input->warehouseId)
                ->lockForUpdate()
                ->findOrFail($input->groupId);

            $supplier = Supplier::forWarehouse($input->warehouseId)
                ->active()
                ->findOrFail($input->supplierId);

            $allocations = PurchaseRequestAllocation::where('purchase_request_group_id', $group->id)
                ->whereNull('purchase_order_item_id')
                ->with('purchaseRequestItem.purchaseRequest')
                ->lockForUpdate()
                ->get();

            if ($allocations->isEmpty()) {
                throw new Exception('This group has no pending allocations to convert into a Purchase Order.');
            }

            $purchaseOrder = PurchaseOrder::create([
                'warehouse_id' => $input->warehouseId,
                'supplier_id' => $supplier->id,
                'po_number' => $this->generatePoNumber($input->warehouseId),
                'status' => PurchaseOrderStatus::Draft->value,
                'created_by' => $actor->id,
                'notes' => $input->notes,
                'purchase_request_group_id' => $group->id,
            ]);

            $unitCosts = collect($input->items)->keyBy('item_id');

            foreach ($allocations->groupBy('purchaseRequestItem.item_id') as $itemId => $itemAllocations) {
                $orderedQuantity = (int) $itemAllocations->sum('allocated_quantity');
                $itemInput = $unitCosts->get((int) $itemId);

                $purchaseOrderItem = $purchaseOrder->items()->create([
                    'item_id' => $itemId,
                    'ordered_quantity' => $orderedQuantity,
                    'unit_cost' => $itemInput['unit_cost'] ?? 0,
                    'notes' => $itemInput['notes'] ?? null,
                ]);

                foreach ($itemAllocations as $allocation) {
                    $allocation->update(['purchase_order_item_id' => $purchaseOrderItem->id]);
                }
            }

            $purchaseRequestIds = $allocations->pluck('purchaseRequestItem.purchase_request_id')->unique();

            PurchaseRequest::whereIn('id', $purchaseRequestIds)
                ->update(['status' => PurchaseRequestStatus::PoCreated->value]);

            event(new PurchaseOrderCreated($purchaseOrder, $actor));

            return $purchaseOrder->load('items.item');
        });
    }

    private function generatePoNumber(int $warehouseId): string
    {
        $date = now()->format('Ymd');

        Warehouse::query()->whereKey($warehouseId)->lockForUpdate()->firstOrFail();

        $sequence = PurchaseOrder::forWarehouse($warehouseId)
            ->where('po_number', 'like', "PO-{$date}-%")
            ->count() + 1;

        return sprintf('PO-%s-%04d', $date, $sequence);
    }
}
