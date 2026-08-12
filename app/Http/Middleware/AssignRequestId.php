<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) config('security.request_id_header', 'X-Request-Id');
        $candidate = (string) $request->headers->get($header, '');
        $pattern = (string) config('security.request_id_pattern');
        $requestId = preg_match($pattern, $candidate) === 1
            ? $candidate
            : Str::uuid()->toString();

        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set($header, $requestId);

        return $response;
    }
}
