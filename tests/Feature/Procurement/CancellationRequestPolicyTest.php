<?php

namespace Tests\Feature\Procurement;

use App\Enums\WarehouseRole;
use App\Models\CancellationRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\CancellationRequestPolicy;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function memberOf(WarehouseRole $role, Warehouse $warehouse, string $status = 'active'): User
    {
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $user->assignRole($role->value);
        $user->warehouseMemberships()->create([
            'warehouse_id' => $warehouse->id,
            'company_id' => $warehouse->company_id,
            'role' => $role->value,
            'status' => $status,
        ]);

        return $user;
    }

    public function test_decide_allows_authorized_role_in_same_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->memberOf(WarehouseRole::KepalaGudang, $warehouse);
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->assertTrue((new CancellationRequestPolicy)->decide($user, $cr));
    }

    public function test_decide_denies_unauthorized_role_in_same_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->memberOf(WarehouseRole::StaffAdmin, $warehouse);
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->assertFalse((new CancellationRequestPolicy)->decide($user, $cr));
    }

    public function test_decide_denies_authorized_role_in_other_warehouse(): void
    {
        $ownWarehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = $this->memberOf(WarehouseRole::KepalaGudang, $ownWarehouse);
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $otherWarehouse->id]);

        $this->assertFalse((new CancellationRequestPolicy)->decide($user, $cr));
    }

    public function test_decide_denies_cross_tenant_object(): void
    {
        $ownWarehouse = Warehouse::factory()->create();
        $otherCompanyWarehouse = Warehouse::factory()->create();
        $user = $this->memberOf(WarehouseRole::KepalaGudang, $ownWarehouse);
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $otherCompanyWarehouse->id]);

        $this->assertFalse((new CancellationRequestPolicy)->decide($user, $cr));
    }

    public function test_decide_denies_inactive_membership(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->memberOf(WarehouseRole::KepalaGudang, $warehouse, status: 'inactive');
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->assertFalse((new CancellationRequestPolicy)->decide($user, $cr));
    }

    public function test_decide_denies_missing_membership(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->assertFalse((new CancellationRequestPolicy)->decide($user, $cr));
    }

    public function test_view_allows_authorized_role_in_same_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->memberOf(WarehouseRole::StaffAdmin, $warehouse);
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->assertTrue((new CancellationRequestPolicy)->view($user, $cr));
    }

    public function test_view_denies_other_warehouse(): void
    {
        $ownWarehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = $this->memberOf(WarehouseRole::StaffAdmin, $ownWarehouse);
        $cr = CancellationRequest::factory()->create(['warehouse_id' => $otherWarehouse->id]);

        $this->assertFalse((new CancellationRequestPolicy)->view($user, $cr));
    }

    public function test_view_any_reflects_cancel_permission(): void
    {
        $warehouse = Warehouse::factory()->create();
        $authorized = $this->memberOf(WarehouseRole::KepalaGudang, $warehouse);
        $unauthorized = $this->memberOf(WarehouseRole::StaffAdmin, $warehouse);

        $policy = new CancellationRequestPolicy;

        $this->assertTrue($policy->viewAny($authorized));
        $this->assertFalse($policy->viewAny($unauthorized));
    }
}
