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

class FileAndExportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_export_is_private_and_not_written_to_public_storage(): void
    {
        Storage::fake('private');

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        Livewire::actingAs($user)->test(Index::class)->call('export');
        $export = ReportExport::query()->sole();

        Storage::disk('private')->assertExists($export->path);
        Storage::disk('public')->assertMissing($export->path);
    }

    public function test_export_download_cannot_cross_a_warehouse_boundary(): void
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
}
