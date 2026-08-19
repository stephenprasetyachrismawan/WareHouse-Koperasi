<?php

return [
    'request_id_header' => 'X-Request-Id',

    'vite_dev_origin' => env('VITE_DEV_SERVER_ORIGIN'),

    'vite_reverb_host' => env('VITE_REVERB_HOST'),

    'vite_reverb_scheme' => env('VITE_REVERB_SCHEME', 'https'),

    'request_id_pattern' => '/\A[A-Za-z0-9._:-]{1,128}\z/',

    'headers' => [
        'content_security_policy' => env(
            'SECURITY_CONTENT_SECURITY_POLICY',
            // 'unsafe-eval' is required by Livewire's default JS runtime,
            // which evaluates wire:click/wire:submit/wire:model expressions
            // via `new Function()`. Without it, every Livewire action is
            // silently blocked client-side. Livewire ships a CSP-safe build
            // (config/livewire.php `csp_safe`) that avoids this, but it
            // restricts Alpine/Livewire expression syntax app-wide and
            // needs a dedicated compatibility pass before adoption.
            "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: blob: https://*.googleusercontent.com; font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-eval' 'unsafe-inline' https://www.gstatic.com https://www.googleapis.com; connect-src 'self' ws: wss: https://*.googleapis.com; media-src 'self' blob:"
        ),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
    ],

    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
    ],
];
