<?php

namespace App\Domain\Procurement\Events;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class PurchaseRequestApproved
{
    use Dispatchable;

    public function __construct(
        public readonly PurchaseRequest $purchaseRequest,
        public readonly User $actor
    ) {}
}
