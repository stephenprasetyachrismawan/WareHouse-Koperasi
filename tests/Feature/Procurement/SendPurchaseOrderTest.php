<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Domain\Procurement\Events\PurchaseOrderSent;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SendPurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    private function createDraftPurchaseOrderWithAllocation(Warehouse $warehouse, User $user): PurchaseOrder
    {
        $supplier = Supplier::factory()->create(['warehouse_id' => $warehouse->id]);
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $purchaseRequestItem = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($purchaseRequestItem->id, 10)],
        ));

        return app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: null,
            items: [['item_id' => $item->id, 'unit_cost' => 5000]],
        ));
    }

    public function test_it_sends_a_draft_purchase_order_and_syncs_linked_requests(): void
    {
        Event::fake([PurchaseOrderSent::class]);

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseOrder = $this->createDraftPurchaseOrderWithAllocation($warehouse, $user);
        $linkedRequestId = $purchaseOrder->items->first()->allocations->first()->purchaseRequestItem->purchase_request_id;

        $sent = app(SendPurchaseOrderAction::class)->execute($user, $purchaseOrder);

        $this->assertEquals(PurchaseOrderStatus::SentToSupplier, $sent->status);
        $this->assertEquals($user->id, $sent->sent_by);
        $this->assertNotNull($sent->sent_at);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $linkedRequestId,
            'status' => PurchaseRequestStatus::PoSent->value,
        ]);

        Event::assertDispatched(PurchaseOrderSent::class);
    }

    public function test_it_rejects_sending_a_non_draft_purchase_order(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::SentToSupplier->value,
        ]);

        $this->expectException(\Exception::class);

        app(SendPurchaseOrderAction::class)->execute($user, $purchaseOrder);
    }

    public function test_it_rejects_sending_a_purchase_order_without_items(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::Draft->value,
        ]);

        $this->expectException(\Exception::class);

        app(SendPurchaseOrderAction::class)->execute($user, $purchaseOrder);
    }
}
