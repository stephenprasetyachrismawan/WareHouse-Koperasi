<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Procurement\Queries\PurchaseRequestInProgressByItemQuery;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestInProgressByItemQueryTest extends TestCase
{
    use RefreshDatabase;

    private function requestWithItem(Warehouse $warehouse, Item $item, int $quantity, \Closure $state): PurchaseRequest
    {
        $purchaseRequest = $state(PurchaseRequest::factory())->for($warehouse)->create();
        PurchaseRequestItem::factory()->for($purchaseRequest)->for($item)->create(['requested_quantity' => $quantity]);

        return $purchaseRequest;
    }

    /**
     * Exact scenario from the spec: only non-terminal statuses contribute,
     * and the total must be 30 (10+15+5), never 80.
     */
    public function test_fr38_totals_only_non_terminal_requests_across_every_source(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->for($warehouse)->create();

        $this->requestWithItem($warehouse, $item, 10, fn ($f) => $f->waitingApproval()->state(['source' => 'MANUAL_STAFF']));
        $this->requestWithItem($warehouse, $item, 15, fn ($f) => $f->approved()->state(['source' => 'CRITICAL_STOCK']));
        $this->requestWithItem($warehouse, $item, 5, fn ($f) => $f->poSent()->state(['source' => 'COOPERATIVE_BACKORDER']));
        $this->requestWithItem($warehouse, $item, 20, fn ($f) => $f->completed()->state(['source' => 'MANUAL_STAFF']));
        $this->requestWithItem($warehouse, $item, 30, fn ($f) => $f->cancelled()->state(['source' => 'MANUAL_STAFF']));

        $result = app(PurchaseRequestInProgressByItemQuery::class)->execute($warehouse->id);

        $this->assertCount(1, $result);
        $this->assertSame(30, $result->first()->total_quantity);
        $this->assertNotSame(80, $result->first()->total_quantity);
    }

    public function test_rejected_requests_never_contribute(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->for($warehouse)->create();
        $this->requestWithItem($warehouse, $item, 50, fn ($f) => $f->rejected());

        $result = app(PurchaseRequestInProgressByItemQuery::class)->execute($warehouse->id);

        $this->assertCount(0, $result);
    }

    public function test_draft_po_created_and_goods_received_all_count_as_in_progress(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->for($warehouse)->create();

        $this->requestWithItem($warehouse, $item, 1, fn ($f) => $f->draft());
        $this->requestWithItem($warehouse, $item, 2, fn ($f) => $f->poCreated());
        $this->requestWithItem($warehouse, $item, 4, fn ($f) => $f->goodsReceived());

        $result = app(PurchaseRequestInProgressByItemQuery::class)->execute($warehouse->id);

        $this->assertSame(7, $result->first()->total_quantity);
    }

    public function test_groups_separately_per_item(): void
    {
        $warehouse = Warehouse::factory()->create();
        $itemA = Item::factory()->for($warehouse)->create(['name' => 'Beras 5kg']);
        $itemB = Item::factory()->for($warehouse)->create(['name' => 'Minyak Goreng']);

        $this->requestWithItem($warehouse, $itemA, 40, fn ($f) => $f->waitingApproval());
        $this->requestWithItem($warehouse, $itemB, 25, fn ($f) => $f->approved());

        $result = app(PurchaseRequestInProgressByItemQuery::class)->execute($warehouse->id);

        $this->assertCount(2, $result);
        $totals = $result->pluck('total_quantity', 'item_name');
        $this->assertSame(40, $totals['Beras 5kg']);
        $this->assertSame(25, $totals['Minyak Goreng']);
    }

    public function test_never_counts_a_different_warehouses_requests(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $itemB = Item::factory()->for($warehouseB)->create();
        $this->requestWithItem($warehouseB, $itemB, 99, fn ($f) => $f->waitingApproval());

        $result = app(PurchaseRequestInProgressByItemQuery::class)->execute($warehouseA->id);

        $this->assertCount(0, $result);
    }

    public function test_multiple_requests_for_the_same_item_sum_together(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->for($warehouse)->create();

        $this->requestWithItem($warehouse, $item, 10, fn ($f) => $f->waitingApproval());
        $this->requestWithItem($warehouse, $item, 15, fn ($f) => $f->approved());

        $result = app(PurchaseRequestInProgressByItemQuery::class)->execute($warehouse->id);

        $this->assertCount(1, $result);
        $this->assertSame(25, $result->first()->total_quantity);
    }
}
