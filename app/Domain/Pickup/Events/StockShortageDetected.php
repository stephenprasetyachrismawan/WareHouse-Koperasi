<?php

namespace App\Domain\Pickup\Events;

use App\Models\Item;
use App\Models\PickupRequest;
use Illuminate\Foundation\Events\Dispatchable;

class StockShortageDetected
{
    use Dispatchable;

    public function __construct(
        public readonly PickupRequest $pickupRequest,
        public readonly Item $item,
        public readonly int $requestedQty,
        public readonly int $shortageQty
    ) {}
}
