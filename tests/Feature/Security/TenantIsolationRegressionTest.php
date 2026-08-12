<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\WarehouseRole;
use App\Livewire\Reports\Index;
use App\Models\ReportExport;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TenantIsolationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_b_cannot_download_warehouse_a_export_by_uuid_swap(): void
    {
        Storage::fake('private');

        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $owner->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);
        WarehouseMembership::factory()->create([
            'user_id' => $otherUser->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        Livewire::actingAs($owner)->test(Index::class)->call('export');
        $export = ReportExport::query()->sole();

        $this->actingAs($otherUser)
            ->get(route('reports.exports.download', $export->uuid))
            ->assertForbidden();
    }

    public function test_suspended_membership_cannot_download_its_previous_export(): void
    {
        Storage::fake('private');

        $warehouse = Warehouse::factory()->create();
        $owner = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $owner->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        Livewire::actingAs($owner)->test(Index::class)->call('export');
        $export = ReportExport::query()->sole();
        $membership->update(['status' => 'suspended']);

        $this->actingAs($owner)
            ->get(route('reports.exports.download', $export->uuid))
            ->assertForbidden();
    }
}
