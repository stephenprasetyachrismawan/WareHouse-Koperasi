<?php

namespace Tests\Feature\Returns;

use App\Enums\PurchaseRequestSource;
use App\Enums\ReturnStatus;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\DemoGoodsReceiptSeeder;
use Database\Seeders\DemoPickupSeeder;
use Database\Seeders\DemoProcurementSeeder;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoReturnDecisionSeeder;
use Database\Seeders\DemoReturnReplacementSeeder;
use Database\Seeders\DemoReturnSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StockFoundationSeeder;
use Database\Seeders\UserAndMembershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoReturnReplacementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_progresses_replacements_for_both_warehouses_and_is_idempotent(): void
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
            DemoReturnDecisionSeeder::class,
        ]);

        $this->seed(DemoReturnReplacementSeeder::class);

        // WH-PUSAT: existing replacement-pending scenario reached ready-for-repickup.
        $pusReady = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario C (Waiting approval) (PUS)')->first();
        $this->assertEquals(ReturnStatus::ReadyForRepickup, $pusReady->status);
        $this->assertNotNull($pusReady->replacement_pickup_request_id);

        // WH-BARAT: existing replacement-pending scenario carried through to completed.
        $barCompleted = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario B (Admin verified) (BAR)')->first();
        $this->assertEquals(ReturnStatus::Completed, $barCompleted->status);

        foreach (['PUS', 'BAR'] as $tag) {
            $pendingPr = ReturnRequest::where('reason_notes', "Demo Seeder Return - Replacement Scenario D (No stock, PR pending approval) ({$tag})")->first();
            $this->assertNotNull($pendingPr, "Expected a no-stock scenario for {$tag}");
            $this->assertEquals(ReturnStatus::ReplacementPending, $pendingPr->status);
            $pr = PurchaseRequest::where('return_request_id', $pendingPr->id)->first();
            $this->assertNotNull($pr);
            $this->assertEquals(PurchaseRequestSource::ReturnReplacement, $pr->source);

            $readyAfterProcurement = ReturnRequest::where('reason_notes', "Demo Seeder Return - Replacement Scenario E (Procurement completed, ready) ({$tag})")->first();
            $this->assertNotNull($readyAfterProcurement, "Expected a procurement-completed scenario for {$tag}");
            $this->assertEquals(ReturnStatus::ReadyForRepickup, $readyAfterProcurement->status);
        }

        $pickupCountBefore = PickupRequest::count();
        $returnCountBefore = ReturnRequest::count();
        $purchaseRequestCountBefore = PurchaseRequest::count();

        // Idempotent re-run.
        $this->seed(DemoReturnReplacementSeeder::class);

        $this->assertSame($pickupCountBefore, PickupRequest::count());
        $this->assertSame($returnCountBefore, ReturnRequest::count());
        $this->assertSame($purchaseRequestCountBefore, PurchaseRequest::count());
    }
}
