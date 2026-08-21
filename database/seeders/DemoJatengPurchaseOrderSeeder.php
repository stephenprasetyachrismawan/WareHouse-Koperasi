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
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestGroup;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Purchase Order lifecycle for WH-JATENG: a Draft PO still being priced, and
 * two Sent POs from different suppliers (fertilizer/seed supplier and the
 * general sembako distributor), feeding DemoJatengGoodsReceiptSeeder.
 */
class DemoJatengPurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::where('code', 'WH-JATENG')->first();
        if ($warehouse) {
            $this->seedForWarehouse($warehouse, 'purchasing.jateng@koperasi.id', 'JTG');
        }
    }

    private function seedForWarehouse(Warehouse $warehouse, string $purchasingEmail, string $prefix): void
    {
        $user = User::where('email', $purchasingEmail)->first();
        $suppliers = Supplier::forWarehouse($warehouse->id)->active()->orderBy('id')->take(2)->get();
        $items = Item::where('warehouse_id', $warehouse->id)->orderBy('id')->take(3)->get();

        if (! $user || $suppliers->isEmpty() || $items->count() < 3) {
            return;
        }

        [$supplierA, $supplierB] = [$suppliers->first(), $suppliers->last()];

        $this->seedPurchaseOrder(
            $warehouse,
            $user,
            $supplierA,
            $items->take(2),
            "Demo Seeder PO - Draft ({$prefix})",
            $prefix.'-DFT',
            unitCost: 150000,
            send: false,
        );

        $this->seedPurchaseOrder(
            $warehouse,
            $user,
            $supplierA,
            $items->take(2),
            "Demo Seeder PO - Sent ({$prefix})",
            $prefix.'-SNT',
            unitCost: 165000,
            send: true,
        );

        $this->seedPurchaseOrder(
            $warehouse,
            $user,
            $supplierB,
            collect([$items->last()]),
            "Demo Seeder PO - Sent Alt Supplier ({$prefix})",
            $prefix.'-SN2',
            unitCost: 45000,
            send: true,
        );
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function seedPurchaseOrder(
        Warehouse $warehouse,
        User $user,
        Supplier $supplier,
        $items,
        string $notes,
        string $prNumberPrefix,
        float $unitCost,
        bool $send,
    ): void {
        if (PurchaseOrder::where('notes', $notes)->exists()) {
            return;
        }

        $group = $this->createApprovedGroup($warehouse, $user, $items, $notes, $prNumberPrefix);

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: $notes,
            items: $items->map(fn (Item $item) => ['item_id' => $item->id, 'unit_cost' => $unitCost])->all(),
        ));

        if ($send) {
            app(SendPurchaseOrderAction::class)->execute($user, $purchaseOrder);
        }
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function createApprovedGroup(Warehouse $warehouse, User $user, Collection $items, string $notes, string $prNumberPrefix): PurchaseRequestGroup
    {
        $purchaseRequest = PurchaseRequest::create([
            'warehouse_id' => $warehouse->id,
            'request_number' => $prNumberPrefix.'-'.now()->format('Ymd').'-'.random_int(10000, 99999),
            'source' => 'MANUAL_STAFF',
            'urgency' => 'NORMAL',
            'status' => PurchaseRequestStatus::Approved->value,
            'created_by' => $user->id,
            'notes' => 'Demo Seeder PR for '.$notes,
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
