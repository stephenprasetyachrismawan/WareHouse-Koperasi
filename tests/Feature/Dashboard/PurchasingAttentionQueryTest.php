<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Procurement\Queries\PurchasingAttentionQuery;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingAttentionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_each_procurement_attention_metric(): void
    {
        $warehouse = Warehouse::factory()->create();

        $approvedRequest = PurchaseRequest::factory()->for($warehouse)->approved()->create();
        $item = Item::factory()->for($warehouse)->create();
        PurchaseRequestItem::factory()->for($approvedRequest)->for($item)->create(['requested_quantity' => 10]);
        PurchaseRequest::factory()->for($warehouse)->approved()->count(2)->create();

        $supplier = Supplier::factory()->for($warehouse)->create();
        PurchaseOrder::factory()->for($warehouse)->for($supplier)->create(['status' => 'DRAFT']);
        PurchaseOrder::factory()->for($warehouse)->for($supplier)->count(2)->create(['status' => 'SENT_TO_SUPPLIER', 'sent_at' => now()]);

        $result = app(PurchasingAttentionQuery::class)->execute($warehouse->id);

        $this->assertSame(3, $result['approvedAwaitingProcurementCount']);
        $this->assertSame(1, $result['groupingCandidateCount']);
        $this->assertSame(1, $result['draftPoCount']);
        $this->assertSame(2, $result['sentPoAwaitingReceiptCount']);
    }

    public function test_recent_goods_receipts_are_ordered_newest_first_and_tenant_scoped(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $older = GoodsReceipt::factory()->for($warehouseA)->create(['received_at' => now()->subDays(2)]);
        $newer = GoodsReceipt::factory()->for($warehouseA)->create(['received_at' => now()]);
        GoodsReceipt::factory()->for($warehouseB)->create(['received_at' => now()]);

        $result = app(PurchasingAttentionQuery::class)->recentGoodsReceipts($warehouseA->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->first()->is($newer));
        $this->assertTrue($result->last()->is($older));
    }
}
