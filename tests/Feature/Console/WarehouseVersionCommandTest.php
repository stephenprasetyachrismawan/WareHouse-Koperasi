<?php

use Illuminate\Support\Facades\Artisan;

it('outputs the application version from composer.json', function () {
    $expected = json_decode(file_get_contents(base_path('composer.json')), true)['version'] ?? 'unknown';

    Artisan::call('warehouse:version');

    expect(trim(Artisan::output()))->toBe($expected);
});
