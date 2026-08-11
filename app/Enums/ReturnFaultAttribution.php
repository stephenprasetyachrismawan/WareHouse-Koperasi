<?php

namespace App\Enums;

/**
 * FR-32: computed, never manually selected by the approver.
 */
enum ReturnFaultAttribution: string
{
    case Warehouse = 'WAREHOUSE';
    case Supplier = 'SUPPLIER';

    public function label(): string
    {
        return match ($this) {
            self::Warehouse => 'Gudang',
            self::Supplier => 'Supplier',
        };
    }
}
