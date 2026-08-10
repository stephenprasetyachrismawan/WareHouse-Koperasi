<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PurchaseOrderSecurityConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(WarehouseRole $role, Warehouse $warehouse): User
    {
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $user->assignRole($role->value);
        $user->warehouseMemberships()->create([
            'warehouse_id' => $warehouse->id,
            'company_id' => $warehouse->company_id,
            'role' => $role->value,
            'status' => 'active',
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, CompanyAndWarehouseSeeder::class]);
    }

    public function test_cross_tenant_purchase_order_access_denied(): void
    {
        $warehouse1 = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse1);

        $warehouse2 = Warehouse::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse2->id]);

        $response = $this->actingAs($user)
            ->get(route('procurement.purchase-orders.show', $purchaseOrder->uuid));

        $response->assertStatus(403);
    }

    public function test_cannot_allocate_a_purchase_request_item_from_another_warehouse(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $otherWarehouse = Warehouse::factory()->create();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);

        $foreignRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $otherWarehouse->id]);
        $foreignItem = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $foreignRequest->id,
            'requested_quantity' => 10,
        ]);

        $this->expectException(\Exception::class);

        app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($foreignItem->id, 5)],
        ));
    }

    public function test_double_send_is_rejected(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
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

        $action = app(SendPurchaseOrderAction::class);
        $action->execute($user, $purchaseOrder);

        $this->expectException(\Exception::class);
        $action->execute($user, $purchaseOrder->fresh());
    }

    public function test_sequential_purchase_orders_receive_unique_numbers_same_day(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $supplier = Supplier::factory()->create(['warehouse_id' => $warehouse->id]);
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $poNumbers = [];

        foreach (range(1, 3) as $i) {
            $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
            $purchaseRequestItem = PurchaseRequestItem::factory()->create([
                'purchase_request_id' => $purchaseRequest->id,
                'item_id' => $item->id,
                'requested_quantity' => 5,
            ]);

            $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
                warehouseId: $warehouse->id,
                notes: null,
                allocations: [new AllocationInput($purchaseRequestItem->id, 5)],
            ));

            $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($user, new CreatePurchaseOrderInput(
                warehouseId: $warehouse->id,
                groupId: $group->id,
                supplierId: $supplier->id,
                notes: null,
                items: [['item_id' => $item->id, 'unit_cost' => 500]],
            ));

            $poNumbers[] = $purchaseOrder->po_number;
        }

        $this->assertCount(3, array_unique($poNumbers));
    }
}
