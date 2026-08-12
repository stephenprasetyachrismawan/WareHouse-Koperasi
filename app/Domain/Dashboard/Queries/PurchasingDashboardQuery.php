<?php

namespace App\Domain\Dashboard\Queries;

use App\Domain\Procurement\Queries\PurchasingAttentionQuery;
use App\Enums\Permission;
use App\Models\WarehouseMembership;
use Illuminate\Support\Carbon;

class PurchasingDashboardQuery
{
    public function __construct(
        private readonly PurchasingAttentionQuery $purchasingAttention,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(WarehouseMembership $membership): array
    {
        $warehouseId = $membership->warehouse_id;
        $canReceipt = $membership->hasPermission(Permission::ReceiptViewAny);

        return [
            'warehouse' => $membership->warehouse,
            'updatedAt' => Carbon::now(),
            'attention' => $this->purchasingAttention->execute($warehouseId, $membership),
            'recentGoodsReceipts' => $this->purchasingAttention->recentGoodsReceipts($warehouseId, 5, $canReceipt),
        ];
    }
}
