<?php

namespace App\Enums;

enum ReturnReasonCode: string
{
    case Damaged = 'DAMAGED';
    case Defective = 'DEFECTIVE';
    case WrongItem = 'WRONG_ITEM';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Barang rusak',
            self::Defective => 'Barang cacat',
            self::WrongItem => 'Barang salah kirim',
            self::Other => 'Lainnya',
        };
    }

    public function requiresNotes(): bool
    {
        return $this === self::Other;
    }
}
