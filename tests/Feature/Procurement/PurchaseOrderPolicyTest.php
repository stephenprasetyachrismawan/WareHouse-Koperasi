<?php

namespace Tests\Feature\Procurement;

use App\Enums\Permission;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function grantPermission(User $user, Warehouse $warehouse, Permission $permission): void
    {
        setPermissionsTeamId($warehouse->company_id);
        $role = Role::firstOrCreate(['name' => 'admin', 'company_id' => $warehouse->company_id]);
        $spatiePermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission->value]);
        $role->givePermissionTo($spatiePermission);
        $user->assignRole($role);

        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'company_id' => $warehouse->company_id,
            'status' => 'active',
        ]);
    }

    public function test_user_with_permission_can_view_any_purchase_order(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::PurchaseOrderViewAny);

        $this->assertTrue($user->can('viewAny', PurchaseOrder::class));
    }

    public function test_user_without_permission_cannot_view_any_purchase_order(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', PurchaseOrder::class));
    }

    public function test_user_with_permission_can_create_purchase_order(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::PurchaseOrderCreate);

        $this->assertTrue($user->can('create', PurchaseOrder::class));
    }

    public function test_user_can_view_purchase_order_only_in_active_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::PurchaseOrderView);

        $ownPurchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
        $foreignPurchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $otherWarehouse->id]);

        $this->assertTrue($user->can('view', $ownPurchaseOrder));
        $this->assertFalse($user->can('view', $foreignPurchaseOrder));
    }

    public function test_user_can_send_purchase_order_only_in_active_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::PurchaseOrderSend);

        $ownPurchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
        $foreignPurchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $otherWarehouse->id]);

        $this->assertTrue($user->can('send', $ownPurchaseOrder));
        $this->assertFalse($user->can('send', $foreignPurchaseOrder));
    }
}
