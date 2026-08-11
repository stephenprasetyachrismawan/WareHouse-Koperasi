<?php

namespace App\Domain\Procurement\ValueObjects;

readonly class RecordGoodsReceiptInput
{
    /**
     * @param  array<int, int>  $receivedQuantities  [purchase_order_item_id => received_quantity]
     */
    public function __construct(
        public int $warehouseId,
        public int $purchaseOrderId,
        public array $receivedQuantities,
        public ?string $notes = null,
    ) {}
}
