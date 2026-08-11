<?php

namespace App\Enums;

enum QualityInspectionResult: string
{
    case Pass = 'PASS';
    case Fail = 'FAIL';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Lolos',
            self::Fail => 'Gagal',
        };
    }
}
