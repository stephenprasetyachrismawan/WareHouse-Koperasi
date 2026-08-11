<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\CheckReplacementAvailabilityAction;
use App\Models\Item;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckReplacementAvailabilityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_available_when_stock_covers_the_required_quantity(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        StockBalance::factory()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 10]);

        $returnRequest = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $warehouse->id]);
        $returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
            'return_quantity' => 4,
        ]);

        $result = app(CheckReplacementAvailabilityAction::class)->execute($returnRequestItem);

        $this->assertTrue($result->isAvailable);
        $this->assertEquals(4, $result->requiredQuantity);
        $this->assertEquals(10, $result->availableQuantity);
        $this->assertEquals(0, $result->shortfallQuantity);
    }

    public function test_it_reports_shortfall_when_stock_is_insufficient(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        StockBalance::factory()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 2]);

        $returnRequest = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $warehouse->id]);
        $returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
            'return_quantity' => 5,
        ]);

        $result = app(CheckReplacementAvailabilityAction::class)->execute($returnRequestItem);

        $this->assertFalse($result->isAvailable);
        $this->assertEquals(3, $result->shortfallQuantity);
    }

    public function test_it_treats_missing_stock_balance_row_as_zero(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $returnRequest = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $warehouse->id]);
        $returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
            'return_quantity' => 1,
        ]);

        $result = app(CheckReplacementAvailabilityAction::class)->execute($returnRequestItem);

        $this->assertFalse($result->isAvailable);
        $this->assertEquals(0, $result->availableQuantity);
    }

    public function test_it_ignores_stock_in_another_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        StockBalance::factory()->create(['warehouse_id' => $otherWarehouse->id, 'item_id' => $item->id, 'quantity' => 100]);

        $returnRequest = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $warehouse->id]);
        $returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
            'return_quantity' => 1,
        ]);

        $result = app(CheckReplacementAvailabilityAction::class)->execute($returnRequestItem);

        $this->assertFalse($result->isAvailable);
        $this->assertEquals(0, $result->availableQuantity);
    }
}
