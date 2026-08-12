<?php

namespace App\Domain\Returns\Queries;

use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;

class PendingReturnVerificationsQuery
{
    public function count(int $warehouseId): int
    {
        return ReturnRequest::forWarehouse($warehouseId)
            ->where('status', ReturnStatus::Submitted->value)
            ->count();
    }
}
