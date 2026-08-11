<?php

namespace Tests\Feature\Procurement;

use App\Enums\Permission;
use App\Models\GoodsReceiptItem;
use App\Models\QualityInspection;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QualityInspectionPolicyTest extends TestCase
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

    public function test_user_with_qc_permission_can_inspect_receipt_item_in_own_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::ReceiptQc);

        $receiptItem = GoodsReceiptItem::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->assertTrue($user->can('create', [QualityInspection::class, $receiptItem]));
    }

    public function test_purchasing_permission_alone_cannot_inspect(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::ReceiptCreate);

        $receiptItem = GoodsReceiptItem::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->assertFalse($user->can('create', [QualityInspection::class, $receiptItem]));
    }

    public function test_cross_tenant_qc_denied(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $this->grantPermission($user, $warehouse, Permission::ReceiptQc);

        $foreignReceiptItem = GoodsReceiptItem::factory()->create(['warehouse_id' => $otherWarehouse->id]);

        $this->assertFalse($user->can('create', [QualityInspection::class, $foreignReceiptItem]));
    }
}
