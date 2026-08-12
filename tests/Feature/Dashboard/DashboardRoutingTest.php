<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function memberWith(WarehouseRole $role): User
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => $role->value,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_kepala_gudang_sees_the_head_dashboard(): void
    {
        $user = $this->memberWith(WarehouseRole::KepalaGudang);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Kepala Gudang');
    }

    public function test_staff_admin_sees_the_staff_dashboard(): void
    {
        $user = $this->memberWith(WarehouseRole::StaffAdmin);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Staff Admin');
    }

    public function test_purchasing_sees_the_purchasing_dashboard(): void
    {
        $user = $this->memberWith(WarehouseRole::Purchasing);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Purchasing');
    }

    public function test_koperasi_sees_the_cooperative_dashboard(): void
    {
        $user = $this->memberWith(WarehouseRole::Koperasi);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Beranda');
    }

    public function test_app_admin_sees_the_app_admin_dashboard(): void
    {
        $user = $this->memberWith(WarehouseRole::AppAdmin);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Administrasi');
    }

    public function test_super_admin_sees_the_platform_dashboard_even_without_any_warehouse_membership(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Platform Dashboard');
    }

    public function test_super_admin_sees_the_platform_dashboard_even_with_a_warehouse_membership(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['is_super_admin' => true]);
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee('Platform Dashboard');
    }

    public function test_a_user_with_no_active_membership_and_not_super_admin_is_denied(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_switching_active_warehouse_shows_metrics_for_the_newly_active_warehouse_only(): void
    {
        $user = User::factory()->create();
        $warehouseA = Warehouse::factory()->create(['name' => 'Gudang A']);
        $warehouseB = Warehouse::factory()->create(['name' => 'Gudang B']);
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'suspended',
        ]);
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gudang B')
            ->assertDontSee('Gudang A');
    }
}
