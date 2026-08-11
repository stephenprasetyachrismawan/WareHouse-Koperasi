<?php

namespace Tests\Feature\Returns;

use App\Enums\WarehouseRole;
use App\Models\ReturnEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReturnTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_koperasi_cannot_view_another_koperasis_return(): void
    {
        $warehouse = Warehouse::factory()->create();

        $koperasiA = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasiA->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $koperasiB = User::factory()->create();
        $membershipB = WarehouseMembership::factory()->create([
            'user_id' => $koperasiB->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->submitted()->create([
            'warehouse_id' => $warehouse->id,
            'cooperative_membership_id' => $membershipB->id,
        ]);

        $this->assertFalse(Gate::forUser($koperasiA)->allows('view', $returnRequest));
        $this->assertTrue(Gate::forUser($koperasiB)->allows('view', $returnRequest));
    }

    public function test_koperasi_cannot_view_a_return_in_another_warehouse(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $koperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $membershipB = WarehouseMembership::factory()->create([
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->submitted()->create([
            'warehouse_id' => $warehouseB->id,
            'cooperative_membership_id' => $membershipB->id,
        ]);

        $this->assertFalse(Gate::forUser($koperasi)->allows('view', $returnRequest));
    }

    public function test_staff_admin_sees_every_return_in_their_warehouse_regardless_of_cooperative(): void
    {
        $warehouse = Warehouse::factory()->create();

        $staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staff->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->submitted()->create(['warehouse_id' => $warehouse->id]);

        $this->assertTrue(Gate::forUser($staff)->allows('view', $returnRequest));
    }

    public function test_staff_admin_from_another_warehouse_cannot_view(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staff->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->submitted()->create(['warehouse_id' => $warehouseB->id]);

        $this->assertFalse(Gate::forUser($staff)->allows('view', $returnRequest));
    }

    public function test_evidence_download_is_denied_for_a_user_outside_the_return_tenant(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();

        $outsider = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $outsider->id,
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->submitted()->create(['warehouse_id' => $warehouse->id]);
        $evidence = ReturnEvidence::factory()->create([
            'return_request_id' => $returnRequest->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->actingAs($outsider)
            ->get(route('returns.evidence', $evidence->uuid))
            ->assertForbidden();
    }

    public function test_evidence_download_is_allowed_for_the_owning_koperasi(): void
    {
        $warehouse = Warehouse::factory()->create();

        $koperasi = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->submitted()->create([
            'warehouse_id' => $warehouse->id,
            'cooperative_membership_id' => $membership->id,
        ]);
        $evidence = ReturnEvidence::factory()->create([
            'return_request_id' => $returnRequest->id,
            'warehouse_id' => $warehouse->id,
            'path' => 'return-evidence/does-not-exist.jpg',
        ]);

        // Policy passes; 404 is expected only because the fixture file was never actually stored.
        $this->actingAs($koperasi)
            ->get(route('returns.evidence', $evidence->uuid))
            ->assertNotFound();
    }
}
