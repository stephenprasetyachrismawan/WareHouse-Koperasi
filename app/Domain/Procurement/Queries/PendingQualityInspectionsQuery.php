<?php

namespace App\Domain\Procurement\Queries;

use App\Models\GoodsReceiptItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PendingQualityInspectionsQuery
{
    public function execute(int $warehouseId, int $perPage = 10): LengthAwarePaginator
    {
        return GoodsReceiptItem::query()
            ->where('warehouse_id', $warehouseId)
            ->whereDoesntHave('inspection')
            ->with(['item', 'goodsReceipt.purchaseOrder.supplier'])
            ->latest('created_at')
            ->paginate($perPage);
    }
}
