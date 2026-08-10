<?php

namespace App\Domain\Procurement\Events;

use App\Models\PurchaseRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public string $reason
    ) {}
}
