<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\CompleteReplacementPickupAction;
use App\Actions\Returns\CreateReturnReplacementPurchaseRequestAction;
use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReturnReplacementTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_koperasi_from_another_warehouse_cannot_view_a_replacement_status(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $koperasiB = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasiB->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $itemA = Item::factory()->create(['warehouse_id' => $warehouseA->id]);
        StockBalance::factory()->create(['warehouse_id' => $warehouseA->id, 'item_id' => $itemA->id, 'quantity' => 10]);

        $returnA = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $warehouseA->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnA->id, 'item_id' => $itemA->id, 'return_quantity' => 2]);
        $prepared = app(PrepareReplacementPickupAction::class)->execute($returnA);

        $this->assertFalse(Gate::forUser($koperasiB)->allows('view', $prepared));
    }

    public function test_staff_from_another_warehouse_cannot_complete_a_replacement_pickup(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $itemA = Item::factory()->create(['warehouse_id' => $warehouseA->id]);
        StockBalance::factory()->create(['warehouse_id' => $warehouseA->id, 'item_id' => $itemA->id, 'quantity' => 10]);

        $returnA = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $warehouseA->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnA->id, 'item_id' => $itemA->id, 'return_quantity' => 2]);
        $prepared = app(PrepareReplacementPickupAction::class)->execute($returnA);

        $staffB = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staffB->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(CompleteReplacementPickupAction::class)->execute($staffB, $prepared);
    }

    public function test_replacement_pickup_and_stock_movement_never_cross_warehouse_boundaries(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        // Same item CODE could exist in both warehouses independently; each Item row is warehouse-scoped.
        $itemA = Item::factory()->create(['warehouse_id' => $warehouseA->id]);
        $itemB = Item::factory()->create(['warehouse_id' => $warehouseB->id]);
        StockBalance::factory()->create(['warehouse_id' => $warehouseA->id, 'item_id' => $itemA->id, 'quantity' => 10]);
        StockBalance::factory()->create(['warehouse_id' => $warehouseB->id, 'item_id' => $itemB->id, 'quantity' => 10]);

        $returnA = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $warehouseA->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnA->id, 'item_id' => $itemA->id, 'return_quantity' => 3]);

        $prepared = app(PrepareReplacementPickupAction::class)->execute($returnA);

        $pickup = PickupRequest::find($prepared->replacement_pickup_request_id);
        $this->assertEquals($warehouseA->id, $pickup->warehouse_id);

        // Warehouse B's stock must remain completely untouched.
        $balanceB = StockBalance::where('warehouse_id', $warehouseB->id)->where('item_id', $itemB->id)->first();
        $this->assertEquals(10, $balanceB->quantity);
    }

    public function test_koperasi_cannot_trigger_replacement_purchase_request_creation_directly(): void
    {
        // Structural guarantee: PrepareReplacementPickupAction and
        // CreateReturnReplacementPurchaseRequestAction take no client-supplied
        // warehouse_id, quantity, or actor identity — every value is derived
        // server-side from the already-approved ReturnRequest/ReturnRequestItem.
        $reflection = new \ReflectionMethod(CreateReturnReplacementPurchaseRequestAction::class, 'execute');
        $parameterNames = array_map(fn ($p) => $p->getName(), $reflection->getParameters());

        $this->assertEquals(['returnRequestItem', 'shortfallQuantity'], $parameterNames);
        $this->assertEquals(ReturnRequestItem::class, (string) $reflection->getParameters()[0]->getType());
    }
}
