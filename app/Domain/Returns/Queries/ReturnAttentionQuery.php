<?php

namespace App\Domain\Returns\Queries;

use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;

class ReturnAttentionQuery
{
    public function replacementPendingCount(int $warehouseId): int
    {
        return ReturnRequest::forWarehouse($warehouseId)
            ->where('status', ReturnStatus::ReplacementPending->value)
            ->count();
    }
}
