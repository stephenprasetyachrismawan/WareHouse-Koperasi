<?php

namespace Tests\Feature\Pickup;

use App\Enums\WarehouseRole;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $warehouse;

    private PickupRequest $pickupRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->warehouse = Warehouse::factory()->create();
        $this->user = User::factory()->create();
        $this->pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    private function assignRole(WarehouseRole $role): void
    {
        setPermissionsTeamId($this->warehouse->company_id);
        $this->user->assignRole($role->value);
        $this->user->warehouseMemberships()->create([
            'permissions' => array_map(fn ($p) => $p->value, $role->defaultPermissions()),
            'warehouse_id' => $this->warehouse->id,
            'company_id' => $this->warehouse->company_id,
            'role' => $role->value,
            'status' => 'active',
        ]);
    }

    public function test_koperasi_can_create_and_view_but_cannot_approve(): void
    {
        $this->assignRole(WarehouseRole::Koperasi);

        $this->assertTrue($this->user->can('create', PickupRequest::class));
        $this->assertTrue($this->user->can('viewAny', PickupRequest::class));
        $this->assertTrue($this->user->can('view', $this->pickupRequest));

        $this->assertFalse($this->user->can('approve', $this->pickupRequest));
        $this->assertFalse($this->user->can('prepare', $this->pickupRequest));
    }

    public function test_kepala_gudang_can_approve_but_cannot_create(): void
    {
        $this->assignRole(WarehouseRole::KepalaGudang);

        $this->assertFalse($this->user->can('create', PickupRequest::class));
        $this->assertTrue($this->user->can('viewAny', PickupRequest::class));
        $this->assertTrue($this->user->can('view', $this->pickupRequest));

        $this->assertTrue($this->user->can('approve', $this->pickupRequest));
        $this->assertFalse($this->user->can('prepare', $this->pickupRequest));
    }

    public function test_staff_admin_can_prepare_and_fulfill(): void
    {
        $this->assignRole(WarehouseRole::StaffAdmin);

        $this->assertTrue($this->user->can('viewAny', PickupRequest::class));
        $this->assertTrue($this->user->can('view', $this->pickupRequest));

        $this->assertTrue($this->user->can('prepare', $this->pickupRequest));
        $this->assertTrue($this->user->can('fulfill', $this->pickupRequest));

        $this->assertFalse($this->user->can('approve', $this->pickupRequest));
    }

    public function test_cannot_access_other_warehouse_request(): void
    {
        $this->assignRole(WarehouseRole::KepalaGudang);

        $otherWarehouseRequest = PickupRequest::factory()->create();

        $this->assertFalse($this->user->can('view', $otherWarehouseRequest));
        $this->assertFalse($this->user->can('approve', $otherWarehouseRequest));
    }
}
