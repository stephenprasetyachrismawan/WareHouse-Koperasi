<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReconciliationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_ledger_and_balance_is_healthy(): void
    {
        $warehouse = Warehouse::factory()->create(['code' => 'WH-OK']);
        $item = Item::factory()->for($warehouse)->create();
        StockBalance::factory()->for($warehouse)->for($item)->create(['quantity' => 12]);
        StockTransaction::factory()->for($warehouse)->for($item)->create([
            'movement_type' => MovementType::Receipt,
            'signed_quantity' => 12,
            'balance_before' => 0,
            'balance_after' => 12,
        ]);

        $this->artisan('stock:reconcile', ['--warehouse' => $warehouse->id])
            ->expectsOutputToContain("warehouse=WH-OK item={$item->id} ledger=12 balance=12 difference=0 status=HEALTHY")
            ->assertExitCode(0);
    }

    public function test_mismatch_is_reported_without_mutating_the_materialized_balance(): void
    {
        $warehouse = Warehouse::factory()->create(['code' => 'WH-MISMATCH']);
        $item = Item::factory()->for($warehouse)->create();
        $balance = StockBalance::factory()->for($warehouse)->for($item)->create(['quantity' => 10]);
        StockTransaction::factory()->for($warehouse)->for($item)->create([
            'movement_type' => MovementType::Receipt,
            'signed_quantity' => 12,
            'balance_before' => 0,
            'balance_after' => 12,
        ]);

        $this->artisan('stock:reconcile', ['--warehouse' => $warehouse->id])
            ->expectsOutputToContain("warehouse=WH-MISMATCH item={$item->id} ledger=12 balance=10 difference=2 status=MISMATCH")
            ->assertExitCode(1);

        $this->assertSame(10, $balance->fresh()->quantity);
    }

    public function test_all_warehouses_are_reported_individually(): void
    {
        $warehouseA = Warehouse::factory()->create(['code' => 'WH-A']);
        $warehouseB = Warehouse::factory()->create(['code' => 'WH-B']);
        $itemA = Item::factory()->for($warehouseA)->create();
        $itemB = Item::factory()->for($warehouseB)->create();
        StockBalance::factory()->for($warehouseA)->for($itemA)->create(['quantity' => 3]);
        StockBalance::factory()->for($warehouseB)->for($itemB)->create(['quantity' => 7]);
        StockTransaction::factory()->for($warehouseA)->for($itemA)->create(['signed_quantity' => 3, 'balance_before' => 0, 'balance_after' => 3]);
        StockTransaction::factory()->for($warehouseB)->for($itemB)->create(['signed_quantity' => 7, 'balance_before' => 0, 'balance_after' => 7]);

        $this->artisan('stock:reconcile')
            ->expectsOutputToContain('WH-A')
            ->expectsOutputToContain('WH-B')
            ->assertExitCode(0);
    }
}
