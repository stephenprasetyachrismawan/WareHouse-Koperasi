<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Domain\Procurement\Events\PurchaseOrderCreated;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CreatePurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_it_creates_a_purchase_order_aggregating_allocations_by_item(): void
    {
        Event::fake([PurchaseOrderCreated::class]);

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['warehouse_id' => $warehouse->id]);
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $pr1 = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $pr1Item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr1->id,
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        $pr2 = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $pr2Item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr2->id,
            'item_id' => $item->id,
            'requested_quantity' => 8,
        ]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [
                new AllocationInput($pr1Item->id, 6),
                new AllocationInput($pr2Item->id, 4),
            ],
        ));

        $input = new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: 'PO notes',
            items: [
                ['item_id' => $item->id, 'unit_cost' => 15000],
            ],
        );

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, $input);

        $this->assertEquals(PurchaseOrderStatus::Draft, $purchaseOrder->status);
        $this->assertCount(1, $purchaseOrder->items);
        $this->assertEquals(10, $purchaseOrder->items->first()->ordered_quantity);
        $this->assertEquals(15000, (float) $purchaseOrder->items->first()->unit_cost);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr1->id,
            'status' => PurchaseRequestStatus::PoCreated->value,
        ]);
        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr2->id,
            'status' => PurchaseRequestStatus::PoCreated->value,
        ]);

        $this->assertDatabaseHas('purchase_request_allocations', [
            'purchase_request_item_id' => $pr1Item->id,
            'purchase_order_item_id' => $purchaseOrder->items->first()->id,
        ]);

        Event::assertDispatched(PurchaseOrderCreated::class);
    }

    public function test_it_rejects_inactive_or_cross_tenant_supplier(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $inactiveSupplier = Supplier::factory()->create(['warehouse_id' => $warehouse->id, 'is_active' => false]);

        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 10,
        ]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($item->id, 5)],
        ));

        $this->expectException(ModelNotFoundException::class);

        app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $inactiveSupplier->id,
            notes: null,
            items: [],
        ));
    }
}
