<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_response_contains_safe_security_headers_and_request_id(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_valid_request_id_is_propagated_but_injection_is_replaced(): void
    {
        $this->get('/', ['X-Request-Id' => 'request-2026-0001'])
            ->assertHeader('X-Request-Id', 'request-2026-0001');

        $this->get('/', ['X-Request-Id' => "bad\r\nX-Leak: yes"])
            ->assertHeader('X-Request-Id')
            ->assertHeaderMissing('X-Leak');
    }

    public function test_hsts_is_not_added_to_local_http(): void
    {
        config([
            'app.env' => 'local',
            'app.url' => 'http://localhost',
        ]);

        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_added_only_for_production_https(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://warehouse.test',
        ]);

        $this->withServerVariables(['HTTPS' => 'on'])
            ->get('/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
