<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WarehouseRole;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_platform_metrics_without_tenant_business_details(): void
    {
        $activeWarehouse = Warehouse::factory()->create(['name' => 'Gudang Aktif']);
        Warehouse::factory()->create([
            'name' => 'Gudang Suspended',
            'status' => 'suspended',
        ]);
        $appAdmin = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $appAdmin->id,
            'warehouse_id' => $activeWarehouse->id,
            'role' => WarehouseRole::AppAdmin->value,
            'status' => 'active',
        ]);
        PurchaseRequest::factory()->for($activeWarehouse)->create([
            'request_number' => 'TENANT-BUSINESS-SECRET',
        ]);
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($superAdmin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Platform Dashboard')
            ->assertSee('Warehouse Aktif')
            ->assertSee('Warehouse Suspended')
            ->assertSee('Coverage App Admin')
            ->assertDontSee('TENANT-BUSINESS-SECRET')
            ->assertDontSee('Purchase Request');
    }
}
