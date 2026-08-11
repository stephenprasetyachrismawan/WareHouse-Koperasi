<?php

namespace App\Enums;

/**
 * Full Return state machine per BATASAN.md §7.3. Phase 5.1 only implements
 * and wires transitions up to WAITING_APPROVAL; later cases are declared here
 * for forward compatibility so Phase 5.2/5.3 do not need a breaking migration.
 */
enum ReturnStatus: string
{
    case Submitted = 'SUBMITTED';
    case AdminVerified = 'ADMIN_VERIFIED';
    case WaitingApproval = 'WAITING_APPROVAL';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case ReplacementPending = 'REPLACEMENT_PENDING';
    case ReadyForRepickup = 'READY_FOR_REPICKUP';
    case Completed = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::AdminVerified => 'Admin Verified',
            self::WaitingApproval => 'Waiting Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::ReplacementPending => 'Replacement Pending',
            self::ReadyForRepickup => 'Ready for Repickup',
            self::Completed => 'Completed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Completed], true);
    }
}
