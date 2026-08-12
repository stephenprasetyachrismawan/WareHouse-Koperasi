<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WarehouseRole;
use App\Models\GoodsReceipt;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function purchasingUser(Warehouse $warehouse): User
    {
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Purchasing->value,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_the_page_renders_with_zero_state(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->purchasingUser($warehouse);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada penerimaan barang.');
    }

    public function test_recent_receipts_from_another_warehouse_never_leak_in(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $user = $this->purchasingUser($warehouseA);
        GoodsReceipt::factory()->for($warehouseB)->create(['receipt_number' => 'GR-SECRET-9999']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('GR-SECRET-9999');
    }

    public function test_narrowed_membership_permissions_hide_procurement_metrics(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Purchasing->value,
            'status' => 'active',
            'permissions' => ['dashboard.view'],
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Request Disetujui Belum Diproses')
            ->assertDontSee('Kandidat Grouping')
            ->assertSee('Belum ada penerimaan barang.');
    }
}
