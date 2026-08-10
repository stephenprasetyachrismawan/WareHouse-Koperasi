<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Domain\Procurement\Queries\PurchaseOrderTraceabilityQuery;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AllocationTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_it_preserves_exact_source_pr_mapping_after_grouping(): void
    {
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
            'requested_quantity' => 15,
        ]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [
                new AllocationInput($pr1Item->id, 10),
                new AllocationInput($pr2Item->id, 5),
            ],
        ));

        $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: null,
            items: [['item_id' => $item->id, 'unit_cost' => 1000]],
        ));

        $traceability = app(PurchaseOrderTraceabilityQuery::class)->execute($purchaseOrder);

        $this->assertCount(1, $traceability);
        $line = $traceability->first();

        $this->assertEquals(15, $line['ordered_quantity']);
        $this->assertCount(2, $line['allocations']);

        $byRequestNumber = $line['allocations']->pluck('allocated_quantity', 'purchase_request_number');

        $this->assertEquals(10, $byRequestNumber[$pr1->request_number]);
        $this->assertEquals(5, $byRequestNumber[$pr2->request_number]);
    }
}
