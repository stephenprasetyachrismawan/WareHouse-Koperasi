<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WarehouseRole;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function headUser(Warehouse $warehouse): User
    {
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_the_page_renders_with_zero_state(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->headUser($warehouse);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada Purchase Request yang menunggu persetujuan.');
    }

    public function test_pending_approval_count_reflects_real_data(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->headUser($warehouse);
        PurchaseRequest::factory()->for($warehouse)->waitingApproval()->count(3)->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Tidak ada Purchase Request yang menunggu persetujuan.');
    }

    public function test_a_different_warehouses_pending_approvals_never_leak_in(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $user = $this->headUser($warehouseA);
        PurchaseRequest::factory()->for($warehouseB)->waitingApproval()->count(5)->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada Purchase Request yang menunggu persetujuan.');
    }

    public function test_narrowed_membership_permissions_do_not_query_or_render_operational_widgets(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
            'permissions' => ['dashboard.view'],
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('DashboardMetric')
            ->assertDontSee('Stok Kritis')
            ->assertDontSee('Purchase Request Berjalan');
    }
}
