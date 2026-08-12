<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Livewire\Reports\Index;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\ReportExport;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_a_tenant_scoped_stock_report(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouseA->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $itemA = Item::factory()->for($warehouseA)->create(['name' => 'Beras Gudang A']);
        $itemB = Item::factory()->for($warehouseB)->create(['name' => 'Beras Gudang B']);
        StockBalance::factory()->for($warehouseA)->for($itemA)->create(['quantity' => 12]);
        StockBalance::factory()->for($warehouseB)->for($itemB)->create(['quantity' => 99]);

        $this->actingAs($user)->get(route('reports.index', ['type' => 'stock']))
            ->assertOk()
            ->assertSee('Beras Gudang A')
            ->assertDontSee('Beras Gudang B')
            ->assertSee('Terakhir diperbarui');
    }

    public function test_purchase_request_report_uses_server_side_status_filter(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $item = Item::factory()->for($warehouse)->create(['name' => 'Minyak Report']);
        $waiting = PurchaseRequest::factory()->for($warehouse)->waitingApproval()->create([
            'request_number' => 'PR-REPORT-WAITING',
        ]);
        PurchaseRequestItem::factory()->for($waiting)->for($item)->create(['requested_quantity' => 7]);
        $completed = PurchaseRequest::factory()->for($warehouse)->completed()->create([
            'request_number' => 'PR-REPORT-COMPLETED',
        ]);
        PurchaseRequestItem::factory()->for($completed)->for($item)->create(['requested_quantity' => 11]);

        $this->actingAs($user)->get(route('reports.index', [
            'type' => 'purchase_requests',
            'status' => 'WAITING_APPROVAL',
        ]))
            ->assertOk()
            ->assertSee('PR-REPORT-WAITING')
            ->assertDontSee('PR-REPORT-COMPLETED')
            ->assertSee('7');
    }

    public function test_koperasi_cannot_open_internal_operational_reports(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_authorized_csv_export_uses_filters_and_private_storage(): void
    {
        Storage::fake('private');

        $warehouse = Warehouse::factory()->create(['code' => 'WH-EXPORT']);
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);
        $item = Item::factory()->for($warehouse)->create(['name' => 'Item Export']);
        StockBalance::factory()->for($warehouse)->for($item)->create(['quantity' => 21]);

        $response = Livewire::actingAs($user)->test(Index::class)
            ->set('type', 'stock')
            ->call('export');

        $response->assertRedirect();
        $this->assertDatabaseHas('report_exports', [
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'report_type' => 'stock',
            'status' => 'completed',
        ]);

        $export = ReportExport::query()->firstOrFail();
        Storage::disk('private')->assertExists($export->path);
        $this->assertStringContainsString('Item Export', Storage::disk('private')->get($export->path));
        $this->assertStringNotContainsString('evidence', Storage::disk('private')->get($export->path));
        Storage::disk('public')->assertMissing($export->path);
    }

    public function test_app_admin_needs_explicit_export_permission(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::AppAdmin->value,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    }

    public function test_app_admin_with_explicit_operational_permissions_can_view_its_report(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::AppAdmin->value,
            'status' => 'active',
            'permissions' => [
                Permission::ReportsView->value,
                Permission::ReportsExport->value,
                Permission::StockView->value,
            ],
        ]);

        $this->actingAs($user)->get(route('reports.index', ['type' => 'stock']))
            ->assertOk()
            ->assertSee('Export CSV');
    }

    public function test_export_download_is_owner_and_tenant_scoped_and_expires(): void
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

        $export = Livewire::actingAs($owner)->test(Index::class)->call('export');
        $reportExport = ReportExport::query()->firstOrFail();

        $this->actingAs($otherUser)->get(route('reports.exports.download', $reportExport))
            ->assertForbidden();
        $this->actingAs($owner)->get(route('reports.exports.download', $reportExport))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $reportExport->update(['expires_at' => now()->subSecond()]);
        $this->actingAs($owner)->get(route('reports.exports.download', $reportExport))
            ->assertStatus(410);
    }

    public function test_each_authorized_staff_report_read_model_has_a_safe_empty_state(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        foreach (['stock', 'stock_movements', 'purchase_requests', 'pickups', 'returns', 'quality_control'] as $type) {
            $this->actingAs($user)->get(route('reports.index', ['type' => $type]))
                ->assertOk()
                ->assertSee('Tidak ada data untuk filter yang dipilih.');
        }
    }

    public function test_stock_report_does_not_query_once_per_item(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);
        $items = Item::factory()->for($warehouse)->count(100)->create();
        foreach ($items as $item) {
            StockBalance::factory()->for($warehouse)->for($item)->create(['quantity' => 5]);
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get(route('reports.index', ['type' => 'stock']))->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(60, $queryCount, 'Stock report appears to have an N+1 query pattern.');
    }
}
