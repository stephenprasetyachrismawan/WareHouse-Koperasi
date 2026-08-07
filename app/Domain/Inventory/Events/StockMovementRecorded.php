<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use App\Models\StockTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched AFTER COMMIT when a stock movement is successfully recorded.
 *
 * Listeners should use this for side effects: notifications,
 * broadcast, critical stock alerts, etc.
 */
final class StockMovementRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StockTransaction $transaction,
    ) {}
}
