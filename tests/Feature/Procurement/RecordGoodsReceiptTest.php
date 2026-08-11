<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Domain\Procurement\Events\GoodsReceiptRecorded;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RecordGoodsReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    private function sentPurchaseOrderWithItems(Warehouse $warehouse, array $quantities = [10, 5]): PurchaseOrder
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

        return $purchaseOrder->fresh('items');
    }

    public function test_purchasing_can_record_receipt_for_a_sent_purchase_order(): void
    {
        Event::fake([GoodsReceiptRecorded::class]);

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse);

        $quantities = $purchaseOrder->items->pluck('ordered_quantity', 'id')->all();

        $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $quantities,
            notes: 'Diterima lengkap',
        ));

        $this->assertEquals(GoodsReceiptStatus::PendingQc, $receipt->status);
        $this->assertStringStartsWith('GR-', $receipt->receipt_number);
        $this->assertCount(2, $receipt->items);

        $this->assertEquals(PurchaseOrderStatus::GoodsReceived, $purchaseOrder->fresh()->status);

        Event::assertDispatched(GoodsReceiptRecorded::class);
    }

    public function test_draft_purchase_order_cannot_be_received(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::Draft->value,
        ]);
        PurchaseOrderItem::factory()->create(['purchase_order_id' => $purchaseOrder->id, 'ordered_quantity' => 5]);

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: [$purchaseOrder->items->first()->id ?? 0 => 5],
        ));
    }

    public function test_goods_received_purchase_order_cannot_be_received_twice(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse, [10]);
        $quantities = $purchaseOrder->items->pluck('ordered_quantity', 'id')->all();

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $quantities,
        ));

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->fresh()->id,
            receivedQuantities: $quantities,
        ));
    }

    public function test_completed_purchase_order_cannot_be_received(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::Completed->value,
        ]);
        $poItem = PurchaseOrderItem::factory()->create(['purchase_order_id' => $purchaseOrder->id, 'ordered_quantity' => 5]);

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: [$poItem->id => 5],
        ));
    }

    public function test_cross_tenant_purchase_order_cannot_be_received(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($otherWarehouse, [10]);
        $quantities = $purchaseOrder->items->pluck('ordered_quantity', 'id')->all();

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $quantities,
        ));
    }

    public function test_receipt_references_all_purchase_order_items_with_matching_quantities(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse, [10, 5, 3]);
        $quantities = $purchaseOrder->items->pluck('ordered_quantity', 'id')->all();

        $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $quantities,
        ));

        foreach ($purchaseOrder->items as $poItem) {
            $this->assertDatabaseHas('goods_receipt_items', [
                'goods_receipt_id' => $receipt->id,
                'purchase_order_item_id' => $poItem->id,
                'expected_quantity' => $poItem->ordered_quantity,
                'received_quantity' => $poItem->ordered_quantity,
            ]);
        }
    }

    public function test_partial_quantity_is_rejected(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse, [10]);
        $poItem = $purchaseOrder->items->first();

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: [$poItem->id => 7],
        ));
    }

    public function test_excessive_quantity_is_rejected(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse, [10]);
        $poItem = $purchaseOrder->items->first();

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: [$poItem->id => 15],
        ));
    }

    public function test_missing_item_quantity_is_rejected(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse, [10, 5]);

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: [$purchaseOrder->items->first()->id => 10],
        ));
    }

    public function test_receipt_number_is_unique_and_sequential_per_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $numbers = [];
        foreach (range(1, 3) as $i) {
            $po = $this->sentPurchaseOrderWithItems($warehouse, [5]);
            $receipt = app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
                warehouseId: $warehouse->id,
                purchaseOrderId: $po->id,
                receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
            ));
            $numbers[] = $receipt->receipt_number;
        }

        $this->assertCount(3, array_unique($numbers));
    }

    public function test_linked_purchase_requests_transition_to_goods_received(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse, [10]);

        $purchaseRequest = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::PoSent->value,
        ]);
        $prItem = $purchaseRequest->items()->create(['item_id' => $purchaseOrder->items->first()->item_id, 'requested_quantity' => 10]);
        PurchaseRequestAllocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'purchase_request_item_id' => $prItem->id,
            'purchase_order_item_id' => $purchaseOrder->items->first()->id,
            'allocated_quantity' => 10,
        ]);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $purchaseOrder->items->pluck('ordered_quantity', 'id')->all(),
        ));

        $this->assertEquals(PurchaseRequestStatus::GoodsReceived, $purchaseRequest->fresh()->status);
    }

    public function test_recording_receipt_does_not_change_stock_balance_or_create_stock_transaction(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseOrder = $this->sentPurchaseOrderWithItems($warehouse, [10, 5]);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $purchaseOrder->items->pluck('ordered_quantity', 'id')->all(),
        ));

        $this->assertSame(0, StockTransaction::where('warehouse_id', $warehouse->id)->count());
        $this->assertSame(0, StockBalance::where('warehouse_id', $warehouse->id)->count());
    }
}
