<?php

namespace App\Domain\Procurement\Events;

use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoodsReceiptRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public GoodsReceipt $goodsReceipt,
        public User $receivedBy
    ) {}
}
