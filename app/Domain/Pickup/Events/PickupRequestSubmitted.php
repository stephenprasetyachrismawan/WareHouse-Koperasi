<?php

namespace App\Domain\Pickup\Events;

use App\Models\PickupRequest;
use Illuminate\Foundation\Events\Dispatchable;

class PickupRequestSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly PickupRequest $pickupRequest
    ) {}
}
