<?php

namespace App\Domain\Pickup\Events;

use App\Models\PickupRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PickupRequestCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PickupRequest $pickupRequest) {}
}
