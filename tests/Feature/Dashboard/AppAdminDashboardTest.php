<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_admin_sees_only_current_warehouse_administration_metrics(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $user = $this->appAdminUser($warehouse);

        WarehouseMembership::factory()->create([
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);
        WarehouseMembership::factory()->create([
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Purchasing->value,
            'status' => 'suspended',
        ]);
        WarehouseMembership::factory()->create([
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Administrasi')
            ->assertSee('Pengguna Aktif')
            ->assertSee('Membership Aktif')
            ->assertSee('Role Terdaftar')
            ->assertDontSee('Stok Kritis')
            ->assertDontSee('Purchase Request Operasional');
    }

    public function test_operational_cards_require_explicit_membership_permissions(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::AppAdmin->value,
            'status' => 'active',
            'permissions' => [
                Permission::DashboardView->value,
                Permission::StockView->value,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Operasional Terbatas')
            ->assertSee('Stok Kritis');

        $this->assertSame($warehouse->id, $membership->warehouse_id);
    }

    private function appAdminUser(Warehouse $warehouse): User
    {
        $user = User::factory()->create();

        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::AppAdmin->value,
            'status' => 'active',
        ]);

        return $user;
    }
}
