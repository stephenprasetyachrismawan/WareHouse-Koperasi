<?php

namespace App\Enums;

enum PurchaseRequestUrgency: string
{
    case Low = 'LOW';
    case Normal = 'NORMAL';
    case High = 'HIGH';
    case Emergency = 'EMERGENCY';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Normal => 'Normal',
            self::High => 'High',
            self::Emergency => 'Emergency',
        };
    }
}
