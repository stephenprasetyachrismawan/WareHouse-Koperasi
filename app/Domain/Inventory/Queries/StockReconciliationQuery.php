<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Queries;

use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Warehouse;

final class StockReconciliationQuery
{
    /**
     * @return list<array{warehouse_id: int, item_id: int, ledger_total: int, materialized_balance: int, difference: int, healthy: bool}>
     */
    public function forWarehouse(Warehouse $warehouse): array
    {
        $ledgerTotals = StockTransaction::query()
            ->where('warehouse_id', $warehouse->id)
            ->select('item_id')
            ->selectRaw('COALESCE(SUM(signed_quantity), 0) as ledger_total')
            ->groupBy('item_id')
            ->pluck('ledger_total', 'item_id')
            ->map(fn (mixed $total): int => (int) $total);

        $balances = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->pluck('quantity', 'item_id')
            ->map(fn (mixed $quantity): int => (int) $quantity);

        $itemIds = $ledgerTotals->keys()->merge($balances->keys())->unique()->sort()->values();

        return $itemIds->map(function (int|string $itemId) use ($warehouse, $ledgerTotals, $balances): array {
            $ledgerTotal = $ledgerTotals->get($itemId, 0);
            $materializedBalance = $balances->get($itemId, 0);
            $difference = $ledgerTotal - $materializedBalance;

            return [
                'warehouse_id' => $warehouse->id,
                'item_id' => (int) $itemId,
                'ledger_total' => $ledgerTotal,
                'materialized_balance' => $materializedBalance,
                'difference' => $difference,
                'healthy' => $difference === 0,
            ];
        })->all();
    }
}
