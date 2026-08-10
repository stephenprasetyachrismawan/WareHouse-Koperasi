<?php

namespace App\Listeners\Procurement;

use App\Actions\Procurement\CreateBackorderPurchaseRequestAction;
use App\Domain\Pickup\Events\StockShortageDetected;

class CreatePurchaseRequestForStockShortage
{
    public function __construct(
        private readonly CreateBackorderPurchaseRequestAction $action
    ) {}

    public function handle(StockShortageDetected $event): void
    {
        $this->action->execute(
            $event->pickupRequest,
            $event->item,
            $event->requestedQty,
            $event->shortageQty
        );
    }
}
