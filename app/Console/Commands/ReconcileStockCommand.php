<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Inventory\Queries\StockReconciliationQuery;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReconcileStockCommand extends Command
{
    protected $signature = 'stock:reconcile {--warehouse= : Restrict reconciliation to one warehouse ID}';

    protected $description = 'Detect stock ledger and materialized-balance mismatches without changing stock';

    public function handle(StockReconciliationQuery $reconciliation): int
    {
        $warehouseId = $this->option('warehouse');
        $warehouses = Warehouse::query()
            ->when($warehouseId !== null, fn ($query) => $query->whereKey($warehouseId))
            ->orderBy('id')
            ->get();

        if ($warehouses->isEmpty()) {
            $this->error('No matching warehouse found.');

            return self::FAILURE;
        }

        $hasMismatch = false;

        foreach ($warehouses as $warehouse) {
            $lock = Cache::lock("warehouse:{$warehouse->id}:stock-reconciliation", 300);

            if (! $lock->get()) {
                $this->warn("warehouse={$warehouse->code} status=SKIPPED reason=lock-held");
                $hasMismatch = true;

                continue;
            }

            try {
                foreach ($reconciliation->forWarehouse($warehouse) as $row) {
                    $status = $row['healthy'] ? 'HEALTHY' : 'MISMATCH';
                    $this->line(
                        "warehouse={$warehouse->code} item={$row['item_id']} ledger={$row['ledger_total']} "
                        ."balance={$row['materialized_balance']} difference={$row['difference']} status={$status}"
                    );

                    if (! $row['healthy']) {
                        $hasMismatch = true;
                        Log::warning('Stock reconciliation mismatch detected.', [
                            'warehouse_id' => $row['warehouse_id'],
                            'item_id' => $row['item_id'],
                            'ledger_total' => $row['ledger_total'],
                            'materialized_balance' => $row['materialized_balance'],
                            'difference' => $row['difference'],
                        ]);
                    }
                }
            } finally {
                $lock->release();
            }
        }

        return $hasMismatch ? self::FAILURE : self::SUCCESS;
    }
}
