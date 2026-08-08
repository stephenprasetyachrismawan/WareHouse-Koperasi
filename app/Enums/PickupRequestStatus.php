<?php

namespace App\Enums;

enum PickupRequestStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case Checked = 'CHECKED';
    case Backordered = 'BACKORDERED';
    case Prepared = 'PREPARED';
    case WaitingApproval = 'WAITING_APPROVAL';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case ReadyForPickup = 'READY_FOR_PICKUP';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Checked => 'Checked',
            self::Backordered => 'Backordered',
            self::Prepared => 'Prepared',
            self::WaitingApproval => 'Waiting Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Cancelled,
            self::Rejected,
        ], true);
    }
}
