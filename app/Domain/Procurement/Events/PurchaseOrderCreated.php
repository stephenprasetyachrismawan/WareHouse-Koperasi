<?php

namespace App\Domain\Procurement\Events;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public User $createdBy
    ) {}
}
