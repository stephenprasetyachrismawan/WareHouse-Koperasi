<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\ApprovePurchaseCancellationAction;
use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\DirectCancelPurchaseRequestAction;
use App\Actions\Procurement\RequestPurchaseCancellationAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CancellationBoundaryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_direct_cancel_releases_group_only_allocation(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 10,
        ]);

        app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($item->id, 6)],
        ));

        $this->assertDatabaseHas('purchase_request_allocations', [
            'purchase_request_item_id' => $item->id,
        ]);

        app(DirectCancelPurchaseRequestAction::class)->execute($user, $purchaseRequest, 'No longer needed');

        $this->assertDatabaseMissing('purchase_request_allocations', [
            'purchase_request_item_id' => $item->id,
        ]);
    }

    public function test_direct_cancel_shrinks_draft_po_item_when_partially_allocated(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['warehouse_id' => $warehouse->id]);
        $sharedItem = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $pr1 = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $pr1Item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr1->id,
            'item_id' => $sharedItem->id,
            'requested_quantity' => 10,
        ]);

        $pr2 = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $pr2Item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr2->id,
            'item_id' => $sharedItem->id,
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

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: null,
            items: [['item_id' => $sharedItem->id, 'unit_cost' => 1000]],
        ));

        $poItemId = $purchaseOrder->items->first()->id;

        app(DirectCancelPurchaseRequestAction::class)->execute($user, $pr1, 'Cancel PR1');

        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $poItemId,
            'ordered_quantity' => 4,
        ]);

        $this->assertDatabaseMissing('purchase_request_allocations', [
            'purchase_request_item_id' => $pr1Item->id,
        ]);

        $this->assertDatabaseHas('purchase_request_allocations', [
            'purchase_request_item_id' => $pr2Item->id,
            'purchase_order_item_id' => $poItemId,
        ]);
    }

    public function test_direct_cancel_removes_draft_po_item_when_fully_released(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
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

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: null,
            items: [['item_id' => $item->id, 'unit_cost' => 1000]],
        ));

        $poItemId = $purchaseOrder->items->first()->id;

        app(DirectCancelPurchaseRequestAction::class)->execute($user, $purchaseRequest, 'Cancel entirely');

        $this->assertDatabaseMissing('purchase_order_items', ['id' => $poItemId]);
    }

    public function test_direct_cancel_is_rejected_once_linked_po_is_sent(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
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

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: null,
            items: [['item_id' => $item->id, 'unit_cost' => 1000]],
        ));

        app(SendPurchaseOrderAction::class)->execute($user, $purchaseOrder);

        $this->expectException(\Exception::class);

        app(DirectCancelPurchaseRequestAction::class)->execute($user, $purchaseRequest->fresh(), 'Too late');
    }

    public function test_request_cancellation_approval_releases_draft_po_allocation(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
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

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: null,
            items: [['item_id' => $item->id, 'unit_cost' => 1000]],
        ));

        $cancellationRequest = app(RequestPurchaseCancellationAction::class)->execute($user, $purchaseRequest->fresh(), 'Requesting cancel');

        $result = app(ApprovePurchaseCancellationAction::class)->execute($user, $cancellationRequest);

        $this->assertEquals(PurchaseRequestStatus::Cancelled, $result->status);
        $this->assertDatabaseMissing('purchase_order_items', ['id' => $purchaseOrder->items->first()->id]);
    }
}
