<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Approvals\Queries\PendingApprovalsSummaryQuery;
use App\Models\CancellationRequest;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingApprovalsSummaryQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_pending_decisions_across_all_four_domains(): void
    {
        $warehouse = Warehouse::factory()->create();

        PurchaseRequest::factory()->for($warehouse)->waitingApproval()->count(2)->create();
        PurchaseRequest::factory()->for($warehouse)->approved()->count(5)->create();

        PickupRequest::factory()->for($warehouse)->state(['status' => 'WAITING_APPROVAL'])->count(3)->create();

        ReturnRequest::factory()->for($warehouse)->waitingApproval()->count(1)->create();

        CancellationRequest::factory()->for($warehouse)->create();

        $result = app(PendingApprovalsSummaryQuery::class)->execute($warehouse->id);

        $this->assertSame([
            'purchaseRequests' => 2,
            'pickupRequests' => 3,
            'returns' => 1,
            'cancellations' => 1,
        ], $result);
    }

    public function test_decided_cancellations_are_not_counted(): void
    {
        $warehouse = Warehouse::factory()->create();
        CancellationRequest::factory()->for($warehouse)->create(['status' => 'APPROVED']);

        $result = app(PendingApprovalsSummaryQuery::class)->execute($warehouse->id);

        $this->assertSame(0, $result['cancellations']);
    }

    public function test_never_counts_another_warehouse(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        PurchaseRequest::factory()->for($warehouseB)->waitingApproval()->create();

        $result = app(PendingApprovalsSummaryQuery::class)->execute($warehouseA->id);

        $this->assertSame(0, $result['purchaseRequests']);
    }
}
