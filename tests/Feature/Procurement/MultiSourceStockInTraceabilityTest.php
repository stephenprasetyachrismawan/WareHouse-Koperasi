<?php

namespace Tests\Feature\Procurement;

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
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;
use App\Models\PurchaseRequestItem;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * End-to-end proof that receiving/QC/stock-in for a PO item sourced from
 * multiple Purchase Requests (PR-1=10, PR-2=15, PR-3=5 -> PO item=30)
 * produces exactly ONE +30 stock movement for the physical receipt line,
 * not one movement per contributing source allocation, while every source
 * Purchase Request remains individually traceable and reaches COMPLETED.
 */
class MultiSourceStockInTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_one_physical_receipt_produces_one_stock_movement_regardless_of_source_count(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['warehouse_id' => $warehouse->id]);
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $sources = [
            'PR-1' => 10,
            'PR-2' => 15,
            'PR-3' => 5,
        ];

        $allocations = [];
        $purchaseRequests = [];

        foreach ($sources as $label => $quantity) {
            $pr = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
            $prItem = PurchaseRequestItem::factory()->create([
                'purchase_request_id' => $pr->id,
                'item_id' => $item->id,
                'requested_quantity' => $quantity,
            ]);
            $purchaseRequests[$label] = $pr;
            $allocations[] = new AllocationInput($prItem->id, $quantity);
        }

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: $allocations,
        ));

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: null,
            items: [['item_id' => $item->id, 'unit_cost' => 5000]],
        ));

        $this->assertEquals(30, $purchaseOrder->items->first()->ordered_quantity);

        app(SendPurchaseOrderAction::class)->execute($user, $purchaseOrder);

        $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: [$purchaseOrder->items->first()->id => 30],
        ));

        $inspection = app(CompleteQualityInspectionAction::class)->execute($user, $receipt->items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));

        // Exactly one stock movement for the physical receipt, +30 — not +10 +15 +5 +30.
        $this->assertSame(1, StockTransaction::where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->count());
        $transaction = StockTransaction::where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->first();
        $this->assertEquals(30, $transaction->signed_quantity);
        $this->assertEquals($inspection->id, $transaction->source_id);

        // All three original sources remain individually traceable and complete.
        foreach ($purchaseRequests as $pr) {
            $this->assertEquals(PurchaseRequestStatus::Completed, $pr->fresh()->status);
        }

        // Traceability backwards from the ledger to every original PR is intact.
        $traceableRequestNumbers = PurchaseRequestAllocation::where('purchase_order_item_id', $purchaseOrder->items->first()->id)
            ->with('purchaseRequestItem.purchaseRequest')
            ->get()
            ->map(fn ($allocation) => $allocation->purchaseRequestItem->purchaseRequest->request_number)
            ->unique()
            ->values();

        $this->assertCount(3, $traceableRequestNumbers);
        foreach ($purchaseRequests as $pr) {
            $this->assertContains($pr->request_number, $traceableRequestNumbers);
        }
    }
}
