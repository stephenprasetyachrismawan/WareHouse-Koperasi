<?php

namespace App\Domain\Procurement\Events;

use App\Models\QualityInspection;
use App\Models\StockTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoodsAcceptedIntoStock
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public QualityInspection $inspection,
        public StockTransaction $stockTransaction,
    ) {}
}
