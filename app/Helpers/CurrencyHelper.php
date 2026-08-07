<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Format integer amount to Indonesian Rupiah (e.g. 150000 -> 'Rp 150.000').
     */
    public static function formatRupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
