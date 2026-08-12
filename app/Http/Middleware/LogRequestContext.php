<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next($request);
        $actor = $request->user();

        Log::info('http.request', [
            'request_id' => $request->attributes->get('request_id'),
            'actor_id' => $actor?->getAuthIdentifier(),
            'warehouse_id' => $actor?->activeMembership()?->warehouse_id,
            'action' => $request->route()?->getName() ?? $request->method().' '.$request->path(),
            'outcome' => $response->isSuccessful() ? 'success' : 'failure',
            'status' => $response->getStatusCode(),
            'latency_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
        ]);

        return $response;
    }
}
