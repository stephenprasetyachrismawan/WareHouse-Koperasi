<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = (array) config('security.headers', []);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', (string) ($headers['referrer_policy'] ?? 'strict-origin-when-cross-origin'));
        $response->headers->set('X-Frame-Options', (string) ($headers['frame_options'] ?? 'SAMEORIGIN'));
        $response->headers->set('Content-Security-Policy', (string) ($headers['content_security_policy'] ?? "default-src 'self'"));

        if (
            config('app.env') === 'production'
            && str_starts_with((string) config('app.url'), 'https://')
            && (bool) config('security.hsts.enabled', true)
        ) {
            $maxAge = max(0, (int) config('security.hsts.max_age', 31536000));
            $includeSubdomains = (bool) config('security.hsts.include_subdomains', true)
                ? '; includeSubDomains'
                : '';

            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}{$includeSubdomains}");
        }

        return $response;
    }
}
