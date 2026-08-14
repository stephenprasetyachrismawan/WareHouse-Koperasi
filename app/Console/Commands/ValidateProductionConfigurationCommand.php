<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateProductionConfigurationCommand extends Command
{
    protected $signature = 'ops:validate-production';

    protected $description = 'Validate safe production configuration without printing secret values';

    public function handle(): int
    {
        $privateDisk = (array) config('filesystems.disks.private', []);
        $pgsql = (array) config('database.connections.pgsql', []);
        $redis = (array) config('database.redis.default', []);
        $reverbApps = (array) config('reverb.apps.apps', []);
        $reverb = (array) ($reverbApps[0] ?? []);
        $reverbOrigins = (array) ($reverb['allowed_origins'] ?? []);
        $reverbHost = (string) data_get($reverb, 'options.host', '');
        $viteOrigin = config('security.vite_dev_origin');
        $viteReverbHost = (string) config('security.vite_reverb_host', '');
        $appUrl = (string) config('app.url', '');
        $canonicalOrigin = rtrim($appUrl, '/');
        $reverbCredentialsPresent = filled($reverb['key'] ?? null)
            && filled($reverb['secret'] ?? null)
            && filled($reverb['app_id'] ?? null);
        $privateS3Configured = ($privateDisk['driver'] ?? null) === 's3'
            && ($privateDisk['visibility'] ?? null) === 'private'
            && filled($privateDisk['bucket'] ?? null);

        $checks = [
            'APP_ENV' => config('app.env') === 'production',
            'APP_DEBUG' => config('app.debug') === false,
            'HTTPS_URL' => $this->isPublicHttpsEndpoint($appUrl),
            'APP_KEY' => filled(config('app.key')),
            'SESSION_COOKIE_SECURE' => config('session.secure') === true,
            'DB_CONNECTION' => config('database.default') === 'pgsql',
            'DB_TLS' => in_array(strtolower((string) ($pgsql['sslmode'] ?? '')), ['require', 'verify-full'], true),
            'REDIS_TLS' => str_starts_with(strtolower(trim((string) ($redis['url'] ?? ''))), 'rediss://'),
            'QUEUE_CONNECTION' => config('queue.default') === 'redis',
            'CACHE_STORE' => config('cache.default') === 'redis',
            'SESSION_DRIVER' => config('session.driver') === 'redis',
            'FILESYSTEM_DISK' => config('filesystems.default') === 's3',
            'PRIVATE_STORAGE' => $privateS3Configured,
            'PRIVATE_STORAGE_DRIVER' => $privateS3Configured,
            'BROADCAST_CONNECTION' => config('broadcasting.default') === 'reverb',
            'REVERB_TLS' => (($reverb['options']['scheme'] ?? null) === 'https'),
            'REVERB_HOST' => $this->isPublicEndpoint($reverbHost),
            'REVERB_APP_CREDENTIALS' => $reverbCredentialsPresent,
            'REVERB_ORIGINS' => $reverbOrigins !== []
                && ! in_array('*', $reverbOrigins, true)
                && in_array($canonicalOrigin, array_map(static fn (mixed $origin): string => rtrim((string) $origin, '/'), $reverbOrigins), true),
            'VITE_DEV_SERVER_ORIGIN' => $viteOrigin === null || $this->isPublicHttpsEndpoint((string) $viteOrigin),
            'VITE_REVERB_HOST' => $this->isPublicEndpoint($viteReverbHost),
            'VITE_REVERB_TLS' => strtolower((string) config('security.vite_reverb_scheme')) === 'https',
        ];

        $failed = false;
        foreach ($checks as $name => $passed) {
            $status = $passed ? 'PASS' : 'FAIL';
            $this->line("{$name}={$status}");
            $failed = $failed || ! $passed;
        }

        if ($failed) {
            $this->error('Production configuration is not ready.');

            return self::FAILURE;
        }

        $this->info('Production configuration is ready.');

        return self::SUCCESS;
    }

    private function isPublicHttpsEndpoint(string $endpoint): bool
    {
        return str_starts_with(strtolower(trim($endpoint)), 'https://')
            && $this->isPublicEndpoint($endpoint);
    }

    private function isPublicEndpoint(string $endpoint): bool
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return false;
        }

        $host = parse_url(str_contains($endpoint, '://') ? $endpoint : "https://{$endpoint}", PHP_URL_HOST);

        return is_string($host)
            && ! in_array(strtolower($host), ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true);
    }
}
