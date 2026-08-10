<?php

namespace App\Enums;

enum PurchaseRequestSource: string
{
    case CriticalStock = 'CRITICAL_STOCK';
    case CooperativeBackorder = 'COOPERATIVE_BACKORDER';
    case ManualStaff = 'MANUAL_STAFF';
    case ReturnReplacement = 'RETURN_REPLACEMENT';
    case PredictionNormal = 'PREDICTION_NORMAL';
    case PredictionDirect = 'PREDICTION_DIRECT';

    public function label(): string
    {
        return match ($this) {
            self::CriticalStock => 'Critical Stock',
            self::CooperativeBackorder => 'Cooperative Backorder',
            self::ManualStaff => 'Manual Staff',
            self::ReturnReplacement => 'Return Replacement',
            self::PredictionNormal => 'Prediction Normal',
            self::PredictionDirect => 'Prediction Direct',
        };
    }
}
