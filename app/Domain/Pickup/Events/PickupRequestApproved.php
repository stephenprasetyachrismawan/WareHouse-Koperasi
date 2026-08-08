<?php

namespace App\Domain\Pickup\Events;

use App\Models\Approval;
use App\Models\PickupRequest;
use Illuminate\Foundation\Events\Dispatchable;

class PickupRequestApproved
{
    use Dispatchable;

    public function __construct(
        public readonly PickupRequest $pickupRequest,
        public readonly Approval $approval
    ) {}
}
