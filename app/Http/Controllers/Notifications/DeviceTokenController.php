<?php

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\RegisterDeviceTokenAction;
use App\Actions\Notifications\RevokeDeviceTokenAction;
use App\Domain\Notifications\ValueObjects\RegisterDeviceTokenInput;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ordinary authenticated web session + CSRF protection (the standard `web`
 * middleware group already applied to this route) — there is no separate
 * unauthenticated token-registration endpoint. Device ownership always
 * comes from the authenticated session, never from a client-supplied
 * user_id.
 */
class DeviceTokenController extends Controller
{
    public function store(Request $request, RegisterDeviceTokenAction $action): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'provider' => ['required', 'string', 'in:fcm'],
            'platform' => ['nullable', 'string', 'max:32'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'browser' => ['nullable', 'string', 'max:60'],
        ]);

        $deviceToken = $action->execute(Auth::user(), new RegisterDeviceTokenInput(
            rawToken: $validated['token'],
            provider: $validated['provider'],
            platform: $validated['platform'] ?? 'web',
            deviceName: $validated['device_name'] ?? null,
            browser: $validated['browser'] ?? null,
            userAgentSummary: (string) $request->userAgent(),
        ));

        return response()->json(['uuid' => $deviceToken->uuid], 201);
    }

    public function destroy(DeviceToken $deviceToken, RevokeDeviceTokenAction $action): JsonResponse
    {
        $action->execute(Auth::user(), $deviceToken);

        return response()->json(['revoked' => true]);
    }
}
