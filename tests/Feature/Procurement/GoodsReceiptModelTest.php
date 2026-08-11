<?php

namespace Tests\Feature\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsReceiptModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_uuid_on_creation(): void
    {
        $receipt = GoodsReceipt::factory()->create();

        $this->assertNotNull($receipt->uuid);
    }

    public function test_it_belongs_to_warehouse_purchase_order_and_receiver(): void
    {
        $warehouse = Warehouse::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
        $receiver = User::factory()->create();

        $receipt = GoodsReceipt::factory()->create([
            'warehouse_id' => $warehouse->id,
            'purchase_order_id' => $purchaseOrder->id,
            'received_by' => $receiver->id,
        ]);

        $this->assertTrue($receipt->warehouse->is($warehouse));
        $this->assertTrue($receipt->purchaseOrder->is($purchaseOrder));
        $this->assertTrue($receipt->receiver->is($receiver));
    }

    public function test_it_can_have_items(): void
    {
        $receipt = GoodsReceipt::factory()->create();
        $item = GoodsReceiptItem::factory()->create(['goods_receipt_id' => $receipt->id]);

        $this->assertCount(1, $receipt->items);
        $this->assertTrue($receipt->items->first()->is($item));
    }

    public function test_scope_for_warehouse(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();

        GoodsReceipt::factory()->count(2)->create(['warehouse_id' => $warehouse1->id]);
        GoodsReceipt::factory()->create(['warehouse_id' => $warehouse2->id]);

        $this->assertCount(2, GoodsReceipt::forWarehouse($warehouse1->id)->get());
    }

    public function test_default_status_is_pending_qc(): void
    {
        $receipt = GoodsReceipt::factory()->create();

        $this->assertEquals(GoodsReceiptStatus::PendingQc, $receipt->status);
    }

    public function test_only_one_goods_receipt_allowed_per_purchase_order(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create();
        GoodsReceipt::factory()->create(['purchase_order_id' => $purchaseOrder->id]);

        $this->expectException(QueryException::class);

        GoodsReceipt::factory()->create(['purchase_order_id' => $purchaseOrder->id]);
    }
}
