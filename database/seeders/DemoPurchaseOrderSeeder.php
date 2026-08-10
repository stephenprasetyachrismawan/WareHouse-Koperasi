<?php

namespace Database\Seeders;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class DemoPurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::first();
        if (! $warehouse) {
            return;
        }

        $user = User::first();
        $supplier = Supplier::forWarehouse($warehouse->id)->active()->first();
        $items = Item::where('warehouse_id', $warehouse->id)->take(2)->get();

        if (! $user || ! $supplier || $items->isEmpty()) {
            return;
        }

        Gate::before(fn () => true);

        // Draft PO: still awaiting Purchasing to send it to the supplier.
        $draftGroup = $this->createApprovedGroup($warehouse, $user, $items, 'PRG demo seeder - draft PO');
        app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $draftGroup->id,
            supplierId: $supplier->id,
            notes: 'Demo Seeder PO - Draft',
            items: $items->map(fn (Item $item) => ['item_id' => $item->id, 'unit_cost' => 10000])->all(),
        ));

        // Sent PO: already dispatched to the supplier.
        $sentGroup = $this->createApprovedGroup($warehouse, $user, $items, 'PRG demo seeder - sent PO');
        $sentPurchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $sentGroup->id,
            supplierId: $supplier->id,
            notes: 'Demo Seeder PO - Sent',
            items: $items->map(fn (Item $item) => ['item_id' => $item->id, 'unit_cost' => 12000])->all(),
        ));
        app(SendPurchaseOrderAction::class)->execute($user, $sentPurchaseOrder);
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function createApprovedGroup(Warehouse $warehouse, User $user, $items, string $notes)
    {
        $purchaseRequest = PurchaseRequest::create([
            'warehouse_id' => $warehouse->id,
            'request_number' => 'PR-'.now()->format('Ymd').'-'.random_int(10000, 99999),
            'source' => 'MANUAL_STAFF',
            'urgency' => 'NORMAL',
            'status' => PurchaseRequestStatus::Approved->value,
            'created_by' => $user->id,
            'notes' => 'Demo Seeder PR for Purchase Order grouping',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $allocations = [];
        foreach ($items as $item) {
            $prItem = $purchaseRequest->items()->create([
                'item_id' => $item->id,
                'requested_quantity' => 20,
            ]);

            $allocations[] = new AllocationInput($prItem->id, 20);
        }

        return app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: $notes,
            allocations: $allocations,
        ));
    }
}
