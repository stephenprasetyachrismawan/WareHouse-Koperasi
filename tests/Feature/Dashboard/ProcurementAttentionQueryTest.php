<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Procurement\Queries\ProcurementAttentionQuery;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementAttentionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_in_progress_requests_and_sent_pos_awaiting_receipt(): void
    {
        $warehouse = Warehouse::factory()->create();

        PurchaseRequest::factory()->for($warehouse)->waitingApproval()->count(2)->create();
        PurchaseRequest::factory()->for($warehouse)->approved()->count(1)->create();
        PurchaseRequest::factory()->for($warehouse)->completed()->count(9)->create();
        PurchaseRequest::factory()->for($warehouse)->cancelled()->count(9)->create();

        $supplier = Supplier::factory()->for($warehouse)->create();
        PurchaseOrder::factory()->for($warehouse)->for($supplier)->create(['status' => 'SENT_TO_SUPPLIER', 'sent_at' => now()]);
        PurchaseOrder::factory()->for($warehouse)->for($supplier)->create(['status' => 'DRAFT']);

        $result = app(ProcurementAttentionQuery::class)->execute($warehouse->id);

        $this->assertSame(3, $result['inProgressCount']);
        $this->assertSame(1, $result['poSentAwaitingReceiptCount']);
    }
}
