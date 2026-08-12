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

    public function test_configured_vite_origin_is_allowed_for_dev_assets_without_wildcard_sources(): void
    {
        config([
            'security.vite_dev_origin' => 'https://vite-warehouse.stevewithcode.net',
        ]);

        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression('/script-src [^;]*https:\\/\\/vite-warehouse\\.stevewithcode\\.net/', $csp);
        $this->assertMatchesRegularExpression('/style-src [^;]*https:\\/\\/vite-warehouse\\.stevewithcode\\.net/', $csp);
        $this->assertMatchesRegularExpression('/font-src [^;]*https:\\/\\/vite-warehouse\\.stevewithcode\\.net/', $csp);
        $this->assertMatchesRegularExpression('/connect-src [^;]*https:\\/\\/vite-warehouse\\.stevewithcode\\.net/', $csp);
        $this->assertStringNotContainsString('script-src *', $csp);
    }

    public function test_homepage_scroll_reveal_is_not_blocked_by_csp(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('welcome')
            ->assertDontSee("document.addEventListener('DOMContentLoaded'");
    }
}
