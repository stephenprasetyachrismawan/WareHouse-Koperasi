<?php

namespace App\Enums;

enum PickupRequestSource: string
{
    case Koperasi = 'KOPERASI';
    case ReturnReplacement = 'RETURN_REPLACEMENT';

    public function label(): string
    {
        return match ($this) {
            self::Koperasi => 'Koperasi',
            self::ReturnReplacement => 'Return Replacement',
        };
    }
}
