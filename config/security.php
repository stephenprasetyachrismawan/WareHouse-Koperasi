<?php

return [
    'request_id_header' => 'X-Request-Id',

    'vite_dev_origin' => env('VITE_DEV_SERVER_ORIGIN'),

    'request_id_pattern' => '/\A[A-Za-z0-9._:-]{1,128}\z/',

    'headers' => [
        'content_security_policy' => env(
            'SECURITY_CONTENT_SECURITY_POLICY',
            "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: blob: https://*.googleusercontent.com; font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' https://www.gstatic.com https://www.googleapis.com; connect-src 'self' ws: wss: https://*.googleapis.com; media-src 'self' blob:"
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
