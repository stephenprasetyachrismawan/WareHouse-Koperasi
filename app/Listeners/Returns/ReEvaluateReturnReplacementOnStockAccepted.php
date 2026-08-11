<?php

namespace App\Listeners\Returns;

use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Domain\Procurement\Events\GoodsAcceptedIntoStock;
use App\Domain\Returns\Queries\ReturnReplacementTraceQuery;

/**
 * Wakes a REPLACEMENT_PENDING Return once its fallback Purchase Request's
 * stock has actually been accepted (QC PASS + stock-in). A QC FAIL never
 * dispatches GoodsAcceptedIntoStock, so this listener simply never fires in
 * that case — the Return stays REPLACEMENT_PENDING with no special handling
 * needed. Synchronous, mirroring CreatePurchaseRequestForStockShortage.
 */
class ReEvaluateReturnReplacementOnStockAccepted
{
    public function __construct(
        private readonly ReturnReplacementTraceQuery $trace,
        private readonly PrepareReplacementPickupAction $prepareReplacement,
    ) {}

    public function handle(GoodsAcceptedIntoStock $event): void
    {
        $returnRequest = $this->trace->findLinkedReturnRequest($event->inspection->goodsReceiptItem);

        if (! $returnRequest) {
            return;
        }

        $this->prepareReplacement->execute($returnRequest);
    }
}
