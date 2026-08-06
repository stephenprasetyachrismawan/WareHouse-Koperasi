<?php

use App\Helpers\CurrencyHelper;

test('formats positive integer amount to rupiah currency string', function () {
    expect(CurrencyHelper::formatRupiah(150000))->toBe('Rp 150.000');
});

test('formats zero amount to rupiah currency string', function () {
    expect(CurrencyHelper::formatRupiah(0))->toBe('Rp 0');
});

test('formats negative integer amount to rupiah currency string', function () {
    expect(CurrencyHelper::formatRupiah(-50000))->toBe('Rp -50.000');
});
