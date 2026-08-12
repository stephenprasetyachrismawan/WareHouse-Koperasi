<?php

namespace App\Domain\Notifications\Push;

use App\Models\DeviceToken;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Talks to FCM's HTTP v1 REST API directly via Laravel's Http facade —
 * deliberately no Firebase PHP SDK dependency for the send call either.
 */
class FcmPushNotificationSender implements PushNotificationSender
{
    /**
     * @var list<string>
     */
    private const PERMANENT_ERROR_CODES = ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'];

    public function __construct(
        private readonly FcmAccessTokenProvider $tokenProvider,
    ) {}

    public function send(DeviceToken $deviceToken, PushPayload $payload): PushSendResult
    {
        try {
            $accessToken = $this->tokenProvider->getToken();
        } catch (Throwable $e) {
            Log::warning('Failed to obtain FCM access token.', ['error' => $e->getMessage()]);

            return PushSendResult::failedRetryable('token-unavailable');
        }

        $projectId = config('services.fcm.project_id');

        $response = Http::withToken($accessToken)->post(
            "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
            [
                'message' => [
                    // Decrypted transparently via DeviceToken's `encrypted`
                    // cast — never logged, never stored again beyond this
                    // outbound request.
                    'token' => $deviceToken->encrypted_token,
                    'notification' => [
                        'title' => $payload->title,
                        'body' => $payload->body,
                    ],
                    'data' => [
                        'notification_uuid' => $payload->notificationUuid,
                    ],
                ],
            ]
        );

        if ($response->successful()) {
            return PushSendResult::sent((string) $response->json('name'));
        }

        return $this->classifyFailure($response);
    }

    private function classifyFailure(Response $response): PushSendResult
    {
        $errorCode = (string) ($response->json('error.status') ?? 'UNKNOWN');

        if (in_array($errorCode, self::PERMANENT_ERROR_CODES, true)) {
            return PushSendResult::failedPermanent(strtolower($errorCode));
        }

        return PushSendResult::failedRetryable(strtolower($errorCode));
    }
}
