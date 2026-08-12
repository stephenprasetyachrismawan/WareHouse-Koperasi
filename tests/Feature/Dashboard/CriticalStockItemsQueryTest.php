<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Inventory\Queries\CriticalStockItemsQuery;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalStockItemsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_below_minimum_stock_is_counted_as_critical(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->for($warehouse)->create(['minimum_stock' => 10]);
        StockBalance::factory()->for($item)->for($warehouse)->create(['quantity' => 5]);

        $this->assertSame(1, app(CriticalStockItemsQuery::class)->count($warehouse->id));
    }

    public function test_item_at_or_above_minimum_stock_is_not_critical(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->for($warehouse)->create(['minimum_stock' => 10]);
        StockBalance::factory()->for($item)->for($warehouse)->create(['quantity' => 10]);

        $this->assertSame(0, app(CriticalStockItemsQuery::class)->count($warehouse->id));
    }

    public function test_item_with_no_stock_balance_row_and_a_minimum_is_critical(): void
    {
        $warehouse = Warehouse::factory()->create();
        Item::factory()->for($warehouse)->create(['minimum_stock' => 10]);

        $this->assertSame(1, app(CriticalStockItemsQuery::class)->count($warehouse->id));
    }

    public function test_item_with_no_stock_balance_row_and_zero_minimum_is_not_critical(): void
    {
        $warehouse = Warehouse::factory()->create();
        Item::factory()->for($warehouse)->create(['minimum_stock' => 0]);

        $this->assertSame(0, app(CriticalStockItemsQuery::class)->count($warehouse->id));
    }

    public function test_archived_items_are_excluded(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->for($warehouse)->archived()->create(['minimum_stock' => 10]);
        StockBalance::factory()->for($item)->for($warehouse)->create(['quantity' => 0]);

        $this->assertSame(0, app(CriticalStockItemsQuery::class)->count($warehouse->id));
    }

    public function test_another_warehouses_critical_items_are_never_counted(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $itemB = Item::factory()->for($warehouseB)->create(['minimum_stock' => 10]);
        StockBalance::factory()->for($itemB)->for($warehouseB)->create(['quantity' => 0]);

        $this->assertSame(0, app(CriticalStockItemsQuery::class)->count($warehouseA->id));
        $this->assertSame(1, app(CriticalStockItemsQuery::class)->count($warehouseB->id));
    }

    public function test_paginate_returns_only_critical_items_with_stock_balance_eager_loaded(): void
    {
        $warehouse = Warehouse::factory()->create();
        $critical = Item::factory()->for($warehouse)->create(['minimum_stock' => 10]);
        StockBalance::factory()->for($critical)->for($warehouse)->create(['quantity' => 1]);
        $healthy = Item::factory()->for($warehouse)->create(['minimum_stock' => 10]);
        StockBalance::factory()->for($healthy)->for($warehouse)->create(['quantity' => 100]);

        $result = app(CriticalStockItemsQuery::class)->paginate($warehouse->id);

        $this->assertCount(1, $result->items());
        $this->assertTrue($result->items()[0]->is($critical));
        $this->assertTrue($result->items()[0]->relationLoaded('stockBalance'));
    }
}
