<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'DRAFT';
    case SentToSupplier = 'SENT_TO_SUPPLIER';
    case GoodsReceived = 'GOODS_RECEIVED';
    case Completed = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::SentToSupplier => 'Sent to Supplier',
            self::GoodsReceived => 'Goods Received',
            self::Completed => 'Completed',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed;
    }

    public function canCancelLinkedRequest(): bool
    {
        return $this === self::Draft;
    }
}
