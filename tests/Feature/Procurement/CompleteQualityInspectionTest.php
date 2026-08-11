<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CompleteQualityInspectionAction;
use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Domain\Procurement\Events\GoodsAcceptedIntoStock;
use App\Domain\Procurement\Events\PurchaseOrderCompleted;
use App\Domain\Procurement\Events\QualityInspectionCompleted;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;
use App\Models\QualityInspection;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CompleteQualityInspectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    /**
     * @return array{warehouse: Warehouse, user: User, purchaseOrder: PurchaseOrder, items: Collection<int, GoodsReceiptItem>}
     */
    private function receivedPurchaseOrder(Warehouse $warehouse, User $user, array $quantities = [10]): array
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::SentToSupplier->value,
        ]);

        foreach ($quantities as $quantity) {
            PurchaseOrderItem::factory()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'item_id' => Item::factory()->create(['warehouse_id' => $warehouse->id])->id,
                'ordered_quantity' => $quantity,
            ]);
        }

        $purchaseOrder->refresh()->load('items');

        $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $purchaseOrder->items->pluck('ordered_quantity', 'id')->all(),
        ));

        return [
            'warehouse' => $warehouse,
            'user' => $user,
            'purchaseOrder' => $purchaseOrder,
            'items' => $receipt->items,
        ];
    }

    public function test_pass_creates_exactly_one_inbound_stock_transaction_and_updates_balance(): void
    {
        Event::fake([GoodsAcceptedIntoStock::class, QualityInspectionCompleted::class]);

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['items' => $items] = $this->receivedPurchaseOrder($warehouse, $user, [30]);
        $receiptItem = $items->first();

        $inspection = app(CompleteQualityInspectionAction::class)->execute($user, $receiptItem, new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));

        $this->assertTrue($inspection->isPass());
        $this->assertNotNull($inspection->stock_transaction_id);

        $this->assertSame(1, StockTransaction::where('warehouse_id', $warehouse->id)->where('item_id', $receiptItem->item_id)->count());

        $transaction = StockTransaction::find($inspection->stock_transaction_id);
        $this->assertEquals(30, $transaction->signed_quantity);
        $this->assertEquals(QualityInspection::class, $transaction->source_type);
        $this->assertEquals($inspection->id, $transaction->source_id);

        $balance = StockBalance::where('warehouse_id', $warehouse->id)->where('item_id', $receiptItem->item_id)->first();
        $this->assertEquals(30, $balance->quantity);

        Event::assertDispatched(GoodsAcceptedIntoStock::class);
        Event::assertDispatched(QualityInspectionCompleted::class);
    }

    public function test_fail_does_not_create_stock_transaction_or_change_balance(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['items' => $items] = $this->receivedPurchaseOrder($warehouse, $user, [30]);
        $receiptItem = $items->first();

        $inspection = app(CompleteQualityInspectionAction::class)->execute($user, $receiptItem, new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Fail,
            condition: QualityInspectionCondition::Damaged,
            notes: 'Kemasan rusak parah saat dibuka.',
        ));

        $this->assertFalse($inspection->isPass());
        $this->assertNull($inspection->stock_transaction_id);
        $this->assertSame(0, StockTransaction::where('warehouse_id', $warehouse->id)->count());
        $this->assertSame(0, StockBalance::where('warehouse_id', $warehouse->id)->count());
    }

    public function test_fail_requires_notes(): void
    {
        $warehouse = Warehouse::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Fail,
            condition: QualityInspectionCondition::Damaged,
            notes: '',
        );
    }

    public function test_fail_blocks_purchase_order_completion(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['purchaseOrder' => $purchaseOrder, 'items' => $items] = $this->receivedPurchaseOrder($warehouse, $user, [10]);

        app(CompleteQualityInspectionAction::class)->execute($user, $items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Fail,
            condition: QualityInspectionCondition::Damaged,
            notes: 'Rusak.',
        ));

        $this->assertEquals(PurchaseOrderStatus::GoodsReceived, $purchaseOrder->fresh()->status);
        $this->assertEquals(GoodsReceiptStatus::QcCompleted, GoodsReceipt::where('purchase_order_id', $purchaseOrder->id)->first()->status);
    }

    public function test_duplicate_qc_submission_is_rejected(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['items' => $items] = $this->receivedPurchaseOrder($warehouse, $user, [10]);
        $receiptItem = $items->first();

        $action = app(CompleteQualityInspectionAction::class);
        $action->execute($user, $receiptItem, new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));

        $this->expectException(\Exception::class);

        $action->execute($user, $receiptItem->fresh(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Fail,
            condition: QualityInspectionCondition::Damaged,
            notes: 'Second attempt should not be allowed.',
        ));

        $this->assertSame(1, StockTransaction::where('warehouse_id', $warehouse->id)->count());
    }

    public function test_cross_tenant_qc_is_rejected(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['items' => $items] = $this->receivedPurchaseOrder($otherWarehouse, $user, [10]);

        $this->expectException(\Exception::class);

        app(CompleteQualityInspectionAction::class)->execute($user, $items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));
    }

    public function test_multi_item_po_does_not_complete_until_every_item_passes_qc(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['purchaseOrder' => $purchaseOrder, 'items' => $items] = $this->receivedPurchaseOrder($warehouse, $user, [10, 20, 5]);

        $action = app(CompleteQualityInspectionAction::class);

        $action->execute($user, $items[0], new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));
        $this->assertEquals(PurchaseOrderStatus::GoodsReceived, $purchaseOrder->fresh()->status);

        $action->execute($user, $items[1], new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));
        $this->assertEquals(PurchaseOrderStatus::GoodsReceived, $purchaseOrder->fresh()->status, 'PO must not complete while item 3 is still pending QC.');

        Event::fake([PurchaseOrderCompleted::class]);

        $action->execute($user, $items[2], new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));

        $this->assertEquals(PurchaseOrderStatus::Completed, $purchaseOrder->fresh()->status);
        $this->assertSame(3, StockTransaction::where('warehouse_id', $warehouse->id)->count());
        Event::assertDispatched(PurchaseOrderCompleted::class);
    }

    public function test_source_purchase_request_completes_only_when_its_allocation_passes_qc(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['purchaseOrder' => $purchaseOrder, 'items' => $items] = $this->receivedPurchaseOrder($warehouse, $user, [10]);
        $poItem = $purchaseOrder->items->first();

        $purchaseRequest = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::GoodsReceived->value,
        ]);
        $prItem = $purchaseRequest->items()->create(['item_id' => $poItem->item_id, 'requested_quantity' => 10]);
        PurchaseRequestAllocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'purchase_request_item_id' => $prItem->id,
            'purchase_order_item_id' => $poItem->id,
            'allocated_quantity' => 10,
        ]);

        app(CompleteQualityInspectionAction::class)->execute($user, $items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));

        $this->assertEquals(PurchaseRequestStatus::Completed, $purchaseRequest->fresh()->status);
    }

    public function test_unrelated_purchase_request_is_not_updated(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        ['items' => $items] = $this->receivedPurchaseOrder($warehouse, $user, [10]);

        $unrelatedPr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::Approved->value,
        ]);

        app(CompleteQualityInspectionAction::class)->execute($user, $items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));

        $this->assertEquals(PurchaseRequestStatus::Approved, $unrelatedPr->fresh()->status);
    }
}
