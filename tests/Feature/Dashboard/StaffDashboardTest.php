<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_renders_with_zero_state_and_no_errors(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada Purchase Request yang sedang berjalan.');
    }

    public function test_the_page_renders_real_widget_data(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        PickupRequest::factory()->for($warehouse)->submitted()->create();

        $item = Item::factory()->for($warehouse)->create(['name' => 'Beras 5kg', 'minimum_stock' => 10]);
        StockBalance::factory()->for($item)->for($warehouse)->create(['quantity' => 2]);

        $purchaseRequest = PurchaseRequest::factory()->for($warehouse)->waitingApproval()->create();
        PurchaseRequestItem::factory()->for($purchaseRequest)->for($item)->create(['requested_quantity' => 40]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Beras 5kg');
        $response->assertSee('40 unit');
    }

    public function test_narrowed_membership_permissions_hide_staff_work_queues(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
            'permissions' => ['dashboard.view'],
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Tugas Pengambilan')
            ->assertDontSee('Purchase Request Berjalan per Barang');
    }
}
