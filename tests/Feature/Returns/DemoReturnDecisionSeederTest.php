<?php

namespace Tests\Feature\Returns;

use App\Enums\ReturnFaultAttribution;
use App\Enums\ReturnStatus;
use App\Models\Approval;
use App\Models\ReturnDisposal;
use App\Models\ReturnRequest;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\DemoGoodsReceiptSeeder;
use Database\Seeders\DemoPickupSeeder;
use Database\Seeders\DemoProcurementSeeder;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoReturnDecisionSeeder;
use Database\Seeders\DemoReturnSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StockFoundationSeeder;
use Database\Seeders\UserAndMembershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoReturnDecisionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_decides_returns_for_both_warehouses_and_is_idempotent(): void
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
            DemoReturnSeeder::class,
        ]);

        $this->seed(DemoReturnDecisionSeeder::class);

        foreach (['PUS', 'BAR'] as $tag) {
            $rejected = ReturnRequest::where('reason_notes', 'like', "Demo Seeder Return - Scenario %({$tag})%")
                ->where('status', ReturnStatus::Rejected->value)
                ->first();
            $this->assertNotNull($rejected, "Expected a rejected return for {$tag}");
            $this->assertNotNull($rejected->decision_notes);

            $replacementPending = ReturnRequest::where('reason_notes', 'like', "Demo Seeder Return - Scenario %({$tag})%")
                ->where('status', ReturnStatus::ReplacementPending->value)
                ->first();
            $this->assertNotNull($replacementPending, "Expected a replacement-pending return for {$tag}");
            $this->assertNotNull($replacementPending->fault_attribution);
            $this->assertNotNull(ReturnDisposal::where('return_request_id', $replacementPending->id)->first());

            $pending = ReturnRequest::where('reason_notes', 'like', "Demo Seeder Return - Scenario D%({$tag})%")
                ->where('status', ReturnStatus::WaitingApproval->value)
                ->first();
            $this->assertNotNull($pending, "Expected a still-pending return for {$tag}");
        }

        $pusReplacementPending = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario C (Waiting approval) (PUS)')->first();
        $this->assertEquals(ReturnFaultAttribution::Warehouse, $pusReplacementPending->fault_attribution);

        $barReplacementPending = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario B (Admin verified) (BAR)')->first();
        $this->assertEquals(ReturnFaultAttribution::Supplier, $barReplacementPending->fault_attribution);

        $disposalCountBefore = ReturnDisposal::count();
        $approvalCountBefore = Approval::where('approvable_type', ReturnRequest::class)->count();

        // Re-running must be idempotent: no duplicate decisions or disposals.
        $this->seed(DemoReturnDecisionSeeder::class);

        $this->assertSame($disposalCountBefore, ReturnDisposal::count());
        $this->assertSame($approvalCountBefore, Approval::where('approvable_type', ReturnRequest::class)->count());
    }
}
