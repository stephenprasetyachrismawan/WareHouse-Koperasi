<?php

namespace Tests\Feature\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_uuid_on_creation(): void
    {
        $po = PurchaseOrder::factory()->create();

        $this->assertNotNull($po->uuid);
    }

    public function test_it_belongs_to_warehouse_supplier_and_creator(): void
    {
        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $creator = User::factory()->create();

        $po = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'created_by' => $creator->id,
        ]);

        $this->assertTrue($po->warehouse->is($warehouse));
        $this->assertTrue($po->supplier->is($supplier));
        $this->assertTrue($po->creator->is($creator));
    }

    public function test_it_can_have_items(): void
    {
        $po = PurchaseOrder::factory()->create();
        $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $po->id]);

        $this->assertCount(1, $po->items);
        $this->assertTrue($po->items->first()->is($item));
    }

    public function test_scope_for_warehouse(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();

        PurchaseOrder::factory()->count(2)->create(['warehouse_id' => $warehouse1->id]);
        PurchaseOrder::factory()->create(['warehouse_id' => $warehouse2->id]);

        $this->assertCount(2, PurchaseOrder::forWarehouse($warehouse1->id)->get());
    }

    public function test_scopes_draft_and_sent(): void
    {
        PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft->value]);
        PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::SentToSupplier->value]);
        PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Completed->value]);

        $this->assertCount(1, PurchaseOrder::draft()->get());
        $this->assertCount(2, PurchaseOrder::sent()->get());
    }
}
