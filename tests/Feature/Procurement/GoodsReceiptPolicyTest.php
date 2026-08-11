<?php

namespace Tests\Feature\Procurement;

use App\Enums\Permission;
use App\Models\GoodsReceipt;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoodsReceiptPolicyTest extends TestCase
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

    public function test_user_with_permission_can_view_any_goods_receipt(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::ReceiptViewAny);

        $this->assertTrue($user->can('viewAny', GoodsReceipt::class));
    }

    public function test_user_without_permission_cannot_view_any_goods_receipt(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', GoodsReceipt::class));
    }

    public function test_user_with_permission_can_create_goods_receipt(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::ReceiptCreate);

        $this->assertTrue($user->can('create', GoodsReceipt::class));
    }

    public function test_staff_admin_permission_alone_cannot_create_goods_receipt(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::ReceiptQc);

        $this->assertFalse($user->can('create', GoodsReceipt::class));
    }

    public function test_user_can_view_goods_receipt_only_in_active_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::ReceiptView);

        $ownReceipt = GoodsReceipt::factory()->create(['warehouse_id' => $warehouse->id]);
        $foreignReceipt = GoodsReceipt::factory()->create(['warehouse_id' => $otherWarehouse->id]);

        $this->assertTrue($user->can('view', $ownReceipt));
        $this->assertFalse($user->can('view', $foreignReceipt));
    }
}
