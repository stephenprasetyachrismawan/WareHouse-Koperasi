<?php

namespace App\Helpers;

class CurrencyHelper
{
    public static function formatRupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
