<?php

namespace App\Enums;

enum MovementType: string
{
    case OpeningBalance = 'OPENING_BALANCE';
    case ManualAdjustmentIn = 'MANUAL_ADJUSTMENT_IN';
    case ManualAdjustmentOut = 'MANUAL_ADJUSTMENT_OUT';
    case Receipt = 'RECEIPT';
    case PickupIssue = 'PICKUP_ISSUE';
    case ReturnDisposal = 'RETURN_DISPOSAL';
    case ReplacementIssue = 'REPLACEMENT_ISSUE';
    case Reversal = 'REVERSAL';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Saldo Awal',
            self::ManualAdjustmentIn => 'Penyesuaian Masuk',
            self::ManualAdjustmentOut => 'Penyesuaian Keluar',
            self::Receipt => 'Penerimaan',
            self::PickupIssue => 'Pengambilan',
            self::ReturnDisposal => 'Retur/Pemusnahan',
            self::ReplacementIssue => 'Penggantian',
            self::Reversal => 'Pembatalan',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [
            self::OpeningBalance,
            self::ManualAdjustmentIn,
            self::Receipt,
        ], true);
    }
}
