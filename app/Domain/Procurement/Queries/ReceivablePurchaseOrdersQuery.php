<?php

namespace App\Domain\Procurement\Queries;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReceivablePurchaseOrdersQuery
{
    /**
     * @return LengthAwarePaginator<int, PurchaseOrder>
     */
    public function execute(int $warehouseId, int $perPage = 10): LengthAwarePaginator
    {
        return PurchaseOrder::forWarehouse($warehouseId)
            ->where('status', PurchaseOrderStatus::SentToSupplier->value)
            ->with(['supplier', 'items.item'])
            ->latest('sent_at')
            ->paginate($perPage);
    }
}
