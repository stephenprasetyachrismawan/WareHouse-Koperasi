<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\ApprovePurchaseRequestAction;
use App\Enums\PurchaseRequestStatus;
use App\Enums\WarehouseRole;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementSecurityConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(WarehouseRole $role, Warehouse $warehouse): User
    {
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $user->assignRole($role->value);
        $user->warehouseMemberships()->create([
            'warehouse_id' => $warehouse->id,
            'company_id' => $warehouse->company_id,
            'role' => $role->value,
            'status' => 'active',
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, CompanyAndWarehouseSeeder::class]);
    }

    public function test_cross_tenant_access_denied()
    {
        $warehouse1 = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::KepalaGudang, $warehouse1);

        $warehouse2 = Warehouse::factory()->create();

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse2->id,
            'status' => PurchaseRequestStatus::Draft,
        ]);

        $response = $this->actingAs($user)
            ->get(route('procurement.show', $pr->uuid));

        $response->assertStatus(403);
    }

    public function test_double_submit_idempotency()
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::KepalaGudang, $warehouse);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::WaitingApproval,
        ]);

        $action = app(ApprovePurchaseRequestAction::class);

        $action->execute($user, $pr);

        $this->expectException(\Exception::class);
        $action->execute($user, $pr->fresh());
    }
}
