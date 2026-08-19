<?php

namespace App\Domain\Procurement\Queries;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;

class PurchaseOrderReceivingProgressQuery
{
    /**
     * @return array<int, array{item_name: string, ordered_quantity: int, received_quantity: int|null, qc_result: string|null, stock_in: bool}>
     */
    public function execute(PurchaseOrder $purchaseOrder): array
    {
        return $purchaseOrder->items()
            ->with(['item', 'goodsReceiptItem.inspection'])
            ->get()
            ->map(function (PurchaseOrderItem $poItem): array {
                $receiptItem = $poItem->goodsReceiptItem;
                $inspection = $receiptItem?->inspection;

                return [
                    'item_name' => $poItem->item->name,
                    'ordered_quantity' => $poItem->ordered_quantity,
                    'received_quantity' => $receiptItem?->received_quantity,
                    'qc_result' => $inspection?->result?->label(),
                    'stock_in' => $inspection !== null && $inspection->isPass(),
                ];
            })
            ->all();
    }
}
