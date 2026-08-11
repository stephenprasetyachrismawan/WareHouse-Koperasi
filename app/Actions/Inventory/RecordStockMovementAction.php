<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Domain\Inventory\Events\StockMovementRecorded;
use App\Domain\Inventory\Exceptions\DuplicateStockMovementException;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Records a stock movement atomically.
 *
 * This is the single authoritative way to mutate stock in the system.
 * Every call produces exactly one immutable ledger entry (StockTransaction)
 * and exactly one atomic change to the current StockBalance — even under
 * retries and concurrent requests.
 *
 * Concurrency: uses SELECT ... FOR UPDATE on the StockBalance row.
 * Idempotency: unique(warehouse_id, idempotency_key) prevents duplicates.
 * Atomicity: entire operation runs in a single DB transaction.
 *
 * @see ARCHITECTURE.md §11.1 for the canonical stock movement flow.
 */
final class RecordStockMovementAction
{
    /**
     * Execute the stock movement.
     *
     * @throws DuplicateStockMovementException if idempotency key already used
     */
    public function execute(StockMovementInput $input): StockTransaction
    {
        // Check for duplicate before entering transaction
        $exists = StockTransaction::query()
            ->where('warehouse_id', $input->warehouseId)
            ->where('idempotency_key', $input->idempotencyKey)
            ->exists();

        if ($exists) {
            throw new DuplicateStockMovementException($input->idempotencyKey, $input->warehouseId);
        }

        // Compute signed quantity: positive for inbound, negative for outbound
        $signedQuantity = $input->movementType->isInbound()
            ? $input->quantity
            : -$input->quantity;

        $transaction = DB::transaction(function () use ($input, $signedQuantity): StockTransaction {
            // Get or create the balance row, then lock it for update
            $balance = StockBalance::query()
                ->where('warehouse_id', $input->warehouseId)
                ->where('item_id', $input->itemId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                // Create a new balance row - first movement for this item
                $balance = StockBalance::create([
                    'warehouse_id' => $input->warehouseId,
                    'item_id' => $input->itemId,
                    'quantity' => 0,
                    'version' => 1,
                ]);

                // Re-lock the newly created row
                $balance = StockBalance::query()
                    ->where('id', $balance->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $balanceBefore = $balance->quantity;
            $balanceAfter = $balanceBefore + $signedQuantity;

            // Create the immutable ledger entry
            $transaction = StockTransaction::create([
                'uuid' => (string) Str::uuid(),
                'warehouse_id' => $input->warehouseId,
                'item_id' => $input->itemId,
                'movement_type' => $input->movementType->value,
                'signed_quantity' => $signedQuantity,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $input->sourceType,
                'source_id' => $input->sourceId,
                'reason' => $input->reason,
                'performed_by' => $input->performedBy,
                'idempotency_key' => $input->idempotencyKey,
                'occurred_at' => now(),
                'reversal_of_id' => $input->reversalOfId,
                'metadata' => $input->metadata,
            ]);

            // Atomic balance update with version increment
            $updated = StockBalance::query()
                ->where('id', $balance->id)
                ->where('version', $balance->version)
                ->update([
                    'quantity' => $balanceAfter,
                    'version' => $balance->version + 1,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                // This should not happen with lockForUpdate, but belt-and-suspenders
                throw new \RuntimeException(
                    "StockBalance version conflict for item {$input->itemId} in warehouse {$input->warehouseId}. This indicates a concurrency issue."
                );
            }

            // Registered here (inside the transaction) rather than dispatched
            // right after DB::transaction() returns below, so that when this
            // action is composed inside a larger caller-owned transaction
            // (e.g. QC + stock-in), the event only fires once the OUTERMOST
            // transaction actually commits — never on an uncommitted/rolled
            // back state. If there is no outer transaction, DB::afterCommit()
            // runs the callback immediately, matching prior behaviour.
            DB::afterCommit(function () use ($transaction) {
                StockMovementRecorded::dispatch($transaction);
            });

            return $transaction;
        });

        return $transaction;
    }
}
