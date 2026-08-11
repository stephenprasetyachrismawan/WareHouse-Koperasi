<?php

namespace App\Enums;

enum QualityInspectionCondition: string
{
    case Good = 'GOOD';
    case Damaged = 'DAMAGED';
    case Expired = 'EXPIRED';
    case WrongItem = 'WRONG_ITEM';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Baik',
            self::Damaged => 'Rusak',
            self::Expired => 'Kedaluwarsa',
            self::WrongItem => 'Barang Salah',
            self::Other => 'Lainnya',
        };
    }
}
