<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivilegeEscalationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_admin_without_explicit_operational_permission_cannot_read_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::AppAdmin->value,
            'status' => 'active',
        ]);

        $this->assertFalse($membership->hasPermission(Permission::StockView));
        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    }

    public function test_app_admin_cannot_become_super_admin_through_membership_role_data(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['is_super_admin' => false]);
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->assertFalse($user->fresh()->isSuperAdmin());
        $this->assertFalse($membership->hasPermission(Permission::StockAdjust));
    }
}
