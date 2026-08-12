<?php

namespace App\Domain\Inventory\Queries;

use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single definition of "critical stock" — matches Item::isCritical()
 * exactly (quantity below minimum, or minimum_stock > 0 with no balance row
 * yet). Every critical-stock count/list in the app (Inventory overview,
 * every role dashboard, reports) must go through this query rather than
 * re-deriving the condition, so a widget's count always reconciles with the
 * list it links to.
 */
class CriticalStockItemsQuery
{
    /**
     * @return Builder<Item>
     */
    private function baseQuery(int $warehouseId): Builder
    {
        return Item::query()
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereHas('stockBalance', function (Builder $stockBalanceQuery) {
                    $stockBalanceQuery->whereColumn('quantity', '<', 'items.minimum_stock');
                })->orWhere(function (Builder $noBalance) {
                    $noBalance->whereDoesntHave('stockBalance')
                        ->where('minimum_stock', '>', 0);
                });
            });
    }

    public function count(int $warehouseId): int
    {
        return $this->baseQuery($warehouseId)->count();
    }

    public function paginate(int $warehouseId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($warehouseId)
            ->with('stockBalance')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
