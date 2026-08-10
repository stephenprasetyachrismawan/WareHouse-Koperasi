<?php

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case Draft = 'DRAFT';
    case WaitingApproval = 'WAITING_APPROVAL';
    case Approved = 'APPROVED';
    case PoCreated = 'PO_CREATED';
    case PoSent = 'PO_SENT';
    case GoodsReceived = 'GOODS_RECEIVED';
    case Completed = 'COMPLETED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::WaitingApproval => 'Waiting Approval',
            self::Approved => 'Approved',
            self::PoCreated => 'PO Created',
            self::PoSent => 'PO Sent',
            self::GoodsReceived => 'Goods Received',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Cancelled, self::Completed]);
    }

    public function isInProgress(): bool
    {
        return ! $this->isTerminal();
    }
}
