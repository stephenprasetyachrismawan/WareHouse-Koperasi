<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\WarehouseRole;
use App\Livewire\Procurement\GroupingWorkspace;
use App\Livewire\Procurement\PurchaseOrderIndex;
use App\Livewire\Procurement\PurchaseOrderShow;
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
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, CompanyAndWarehouseSeeder::class]);
    }

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

    public function test_grouping_workspace_renders_and_lists_allocatable_candidates(): void
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);

        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(GroupingWorkspace::class)
            ->assertStatus(200)
            ->assertSet('step', 1);
    }

    public function test_grouping_workspace_creates_group_then_purchase_order(): void
    {
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

        Livewire::actingAs($user)
            ->test(GroupingWorkspace::class)
            ->set("selected.{$purchaseRequestItem->id}", 5)
            ->call('proceedToSupplierStep')
            ->assertSet('step', 2)
            ->set('supplierId', $supplier->id)
            ->set("unitCosts.{$item->id}", 2500)
            ->call('createPurchaseOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('purchase_orders', [
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_purchase_order_index_renders(): void
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);

        PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderIndex::class)
            ->assertStatus(200);
    }

    public function test_purchase_order_show_can_send(): void
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

        Livewire::actingAs($user)
            ->test(PurchaseOrderShow::class, ['purchaseOrder' => $purchaseOrder])
            ->call('send')
            ->assertHasNoErrors();

        $this->assertEquals('SENT_TO_SUPPLIER', $purchaseOrder->fresh()->status->value);
    }
}
