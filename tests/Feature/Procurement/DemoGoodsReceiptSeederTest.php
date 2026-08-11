<?php

namespace Tests\Feature\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\DemoGoodsReceiptSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StockFoundationSeeder;
use Database\Seeders\UserAndMembershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoGoodsReceiptSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_five_scenarios_for_both_warehouses_and_is_idempotent(): void
    {
        $this->seed([
            RoleAndPermissionSeeder::class,
            CompanyAndWarehouseSeeder::class,
            UserAndMembershipSeeder::class,
            MasterDataSeeder::class,
            StockFoundationSeeder::class,
        ]);

        $this->seed(DemoGoodsReceiptSeeder::class);

        foreach (['PUS', 'BAR'] as $tag) {
            $this->assertDatabaseHas('purchase_orders', ['notes' => "Demo Seeder Receipt - Scenario A (Sent, awaiting receipt) ({$tag})"]);
            $this->assertDatabaseHas('purchase_orders', ['notes' => "Demo Seeder Receipt - Scenario B (Received, QC pending) ({$tag})"]);
            $this->assertDatabaseHas('purchase_orders', ['notes' => "Demo Seeder Receipt - Scenario C (QC passed, stock accepted) ({$tag})"]);
            $this->assertDatabaseHas('purchase_orders', ['notes' => "Demo Seeder Receipt - Scenario D (QC failed, stock-in blocked) ({$tag})"]);
            $this->assertDatabaseHas('purchase_orders', ['notes' => "Demo Seeder Receipt - Scenario E (Multi-item, one line still QC pending) ({$tag})"]);

            $scenarioC = PurchaseOrder::where('notes', "Demo Seeder Receipt - Scenario C (QC passed, stock accepted) ({$tag})")->first();
            $this->assertEquals(PurchaseOrderStatus::Completed, $scenarioC->status);

            $scenarioD = PurchaseOrder::where('notes', "Demo Seeder Receipt - Scenario D (QC failed, stock-in blocked) ({$tag})")->first();
            $this->assertEquals(PurchaseOrderStatus::GoodsReceived, $scenarioD->status);

            $scenarioE = PurchaseOrder::where('notes', "Demo Seeder Receipt - Scenario E (Multi-item, one line still QC pending) ({$tag})")->first();
            $this->assertEquals(PurchaseOrderStatus::GoodsReceived, $scenarioE->status);
        }

        // The two warehouses' scenarios must be fully separate records (tenant isolation in the demo data itself).
        $pusScenarioA = PurchaseOrder::where('notes', 'Demo Seeder Receipt - Scenario A (Sent, awaiting receipt) (PUS)')->first();
        $barScenarioA = PurchaseOrder::where('notes', 'Demo Seeder Receipt - Scenario A (Sent, awaiting receipt) (BAR)')->first();
        $this->assertNotEquals($pusScenarioA->warehouse_id, $barScenarioA->warehouse_id);

        $poCountBefore = PurchaseOrder::count();

        // Re-running must be idempotent: no duplicate scenarios.
        $this->seed(DemoGoodsReceiptSeeder::class);

        $this->assertSame($poCountBefore, PurchaseOrder::count());
    }
}
