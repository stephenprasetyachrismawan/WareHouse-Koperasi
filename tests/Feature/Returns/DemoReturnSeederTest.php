<?php

namespace Tests\Feature\Returns;

use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\DemoGoodsReceiptSeeder;
use Database\Seeders\DemoPickupSeeder;
use Database\Seeders\DemoProcurementSeeder;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoReturnSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StockFoundationSeeder;
use Database\Seeders\UserAndMembershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoReturnSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_three_statuses_for_both_warehouses_and_is_idempotent(): void
    {
        $this->seed([
            RoleAndPermissionSeeder::class,
            CompanyAndWarehouseSeeder::class,
            UserAndMembershipSeeder::class,
            MasterDataSeeder::class,
            StockFoundationSeeder::class,
            DemoPickupSeeder::class,
            DemoProcurementSeeder::class,
            DemoPurchaseOrderSeeder::class,
            DemoGoodsReceiptSeeder::class,
        ]);

        $this->seed(DemoReturnSeeder::class);

        foreach (['PUS', 'BAR'] as $tag) {
            $this->assertDatabaseHas('return_requests', ['reason_notes' => "Demo Seeder Return - Scenario A (Submitted, awaiting verification) ({$tag})"]);
            $this->assertDatabaseHas('return_requests', ['reason_notes' => "Demo Seeder Return - Scenario B (Admin verified) ({$tag})"]);

            $scenarioA = ReturnRequest::where('reason_notes', "Demo Seeder Return - Scenario A (Submitted, awaiting verification) ({$tag})")->first();
            $this->assertEquals(ReturnStatus::Submitted, $scenarioA->status);

            $scenarioB = ReturnRequest::where('reason_notes', "Demo Seeder Return - Scenario B (Admin verified) ({$tag})")->first();
            $this->assertEquals(ReturnStatus::AdminVerified, $scenarioB->status);

            $scenarioC = ReturnRequest::where('reason_notes', 'like', "Demo Seeder Return - Scenario C (Waiting approval) ({$tag})%")->first();
            $this->assertNotNull($scenarioC);
            $this->assertEquals(ReturnStatus::WaitingApproval, $scenarioC->status);
        }

        // Tenant isolation in the demo data itself.
        $pusScenarioA = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario A (Submitted, awaiting verification) (PUS)')->first();
        $barScenarioA = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario A (Submitted, awaiting verification) (BAR)')->first();
        $this->assertNotEquals($pusScenarioA->warehouse_id, $barScenarioA->warehouse_id);

        $countBefore = ReturnRequest::count();

        $this->seed(DemoReturnSeeder::class);

        $this->assertSame($countBefore, ReturnRequest::count());
    }
}
