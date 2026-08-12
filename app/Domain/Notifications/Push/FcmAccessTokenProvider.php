<?php

namespace App\Domain\Notifications\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Exchanges a Firebase service-account credentials file for a short-lived
 * OAuth2 access token via the JWT bearer grant. Deliberately no Firebase/
 * Google SDK dependency — just the documented HTTP flow plus PHP's built-in
 * openssl extension for RS256 signing, per the project's policy of
 * justifying every new composer package.
 */
class FcmAccessTokenProvider
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const CACHE_KEY = 'fcm:access_token';

    public function getToken(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(50), fn () => $this->requestNewToken());
    }

    private function requestNewToken(): string
    {
        $credentialsPath = config('services.fcm.credentials_path');

        if (! $credentialsPath || ! File::exists($credentialsPath)) {
            throw new RuntimeException('FCM service account credentials file is not configured or not found.');
        }

        $credentials = json_decode(File::get($credentialsPath), true);
        $jwt = $this->buildSignedJwt($credentials);

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to obtain FCM access token.');
        }

        return (string) $response->json('access_token');
    }

    /**
     * @param  array{client_email?: string, private_key?: string}  $credentials
     */
    private function buildSignedJwt(array $credentials): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'] ?? '',
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $signingInput = "{$header}.{$claims}";

        openssl_sign($signingInput, $signature, $credentials['private_key'] ?? '', OPENSSL_ALGO_SHA256);

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
