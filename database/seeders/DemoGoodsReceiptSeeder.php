<?php

namespace Database\Seeders;

use App\Actions\Procurement\CompleteQualityInspectionAction;
use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\PurchaseRequestStatus;
use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Demonstrates the full Phase 4.3 receiving/QC lifecycle with realistic
 * scenarios A-E from the design brief. Idempotent: skips scenarios whose
 * marker Purchase Order already exists so re-running seeders is safe.
 */
class DemoGoodsReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::first();
        $user = User::first();
        $supplier = Supplier::forWarehouse($warehouse?->id ?? 0)->active()->first();
        $items = $warehouse ? Item::where('warehouse_id', $warehouse->id)->take(2)->get() : collect();

        if (! $warehouse || ! $user || ! $supplier || $items->count() < 2) {
            return;
        }

        Gate::before(fn () => true);

        $this->scenarioA_sentAwaitingReceipt($warehouse, $user, $supplier, $items);
        $this->scenarioB_receivedQcPending($warehouse, $user, $supplier, $items);
        $this->scenarioC_qcPassedStockAccepted($warehouse, $user, $supplier, $items);
        $this->scenarioD_qcFailedStockBlocked($warehouse, $user, $supplier, $items);
        $this->scenarioE_multiItemPartiallyInspected($warehouse, $user, $supplier, $items);
    }

    private function scenarioA_sentAwaitingReceipt(Warehouse $warehouse, User $user, Supplier $supplier, Collection $items): void
    {
        $notes = 'Demo Seeder Receipt - Scenario A (Sent, awaiting receipt)';
        if (PurchaseOrder::where('notes', $notes)->exists()) {
            return;
        }

        $this->sendPurchaseOrder($warehouse, $user, $supplier, $items->take(1), $notes);
    }

    private function scenarioB_receivedQcPending(Warehouse $warehouse, User $user, Supplier $supplier, Collection $items): void
    {
        $notes = 'Demo Seeder Receipt - Scenario B (Received, QC pending)';
        if (PurchaseOrder::where('notes', $notes)->exists()) {
            return;
        }

        $po = $this->sendPurchaseOrder($warehouse, $user, $supplier, $items->take(1), $notes);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $po->id,
            receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
        ));
    }

    private function scenarioC_qcPassedStockAccepted(Warehouse $warehouse, User $user, Supplier $supplier, Collection $items): void
    {
        $notes = 'Demo Seeder Receipt - Scenario C (QC passed, stock accepted)';
        if (PurchaseOrder::where('notes', $notes)->exists()) {
            return;
        }

        $po = $this->sendPurchaseOrder($warehouse, $user, $supplier, $items->take(1), $notes);

        $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $po->id,
            receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
        ));

        app(CompleteQualityInspectionAction::class)->execute($user, $receipt->items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
            notes: 'Barang sesuai pesanan, kemasan baik.',
        ));
    }

    private function scenarioD_qcFailedStockBlocked(Warehouse $warehouse, User $user, Supplier $supplier, Collection $items): void
    {
        $notes = 'Demo Seeder Receipt - Scenario D (QC failed, stock-in blocked)';
        if (PurchaseOrder::where('notes', $notes)->exists()) {
            return;
        }

        $po = $this->sendPurchaseOrder($warehouse, $user, $supplier, $items->take(1), $notes);

        $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $po->id,
            receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
        ));

        app(CompleteQualityInspectionAction::class)->execute($user, $receipt->items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Fail,
            condition: QualityInspectionCondition::Damaged,
            notes: 'Kemasan basah dan sebagian barang rusak saat diperiksa.',
        ));
    }

    private function scenarioE_multiItemPartiallyInspected(Warehouse $warehouse, User $user, Supplier $supplier, Collection $items): void
    {
        $notes = 'Demo Seeder Receipt - Scenario E (Multi-item, one line still QC pending)';
        if (PurchaseOrder::where('notes', $notes)->exists()) {
            return;
        }

        $po = $this->sendPurchaseOrder($warehouse, $user, $supplier, $items->take(2), $notes);

        $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $po->id,
            receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
        ));

        // Only the first line is inspected; the PO must stay at GOODS_RECEIVED,
        // not COMPLETED, while the second line awaits QC.
        app(CompleteQualityInspectionAction::class)->execute($user, $receipt->items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));
    }

    private function sendPurchaseOrder(Warehouse $warehouse, User $user, Supplier $supplier, Collection $items, string $notes): PurchaseOrder
    {
        $purchaseRequest = PurchaseRequest::create([
            'warehouse_id' => $warehouse->id,
            'request_number' => 'PR-'.now()->format('Ymd').'-'.random_int(10000, 99999),
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
                'requested_quantity' => 15,
            ]);
            $allocations[] = new AllocationInput($prItem->id, 15);
        }

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: $notes,
            allocations: $allocations,
        ));

        $po = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: $notes,
            items: $items->map(fn (Item $item) => ['item_id' => $item->id, 'unit_cost' => 8000])->all(),
        ));

        return app(SendPurchaseOrderAction::class)->execute($user, $po)->load('items');
    }
}
