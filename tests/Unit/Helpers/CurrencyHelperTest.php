<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CurrencyHelper;

test('formats integer amount to rupiah currency format', function () {
    expect(CurrencyHelper::formatRupiah(150000))->toBe('Rp 150.000');
    expect(CurrencyHelper::formatRupiah(0))->toBe('Rp 0');
    expect(CurrencyHelper::formatRupiah(1000))->toBe('Rp 1.000');
    expect(CurrencyHelper::formatRupiah(-50000))->toBe('Rp -50.000');
});
