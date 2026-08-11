<?php

namespace App\Enums;

enum GoodsReceiptStatus: string
{
    case PendingQc = 'PENDING_QC';
    case QcCompleted = 'QC_COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::PendingQc => 'Menunggu QC',
            self::QcCompleted => 'QC Selesai',
        };
    }
}
