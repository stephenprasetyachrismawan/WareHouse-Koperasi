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
        $privateRoot = (string) config('filesystems.disks.private.root');
        $reverbApps = (array) config('reverb.apps.apps', []);
        $reverb = (array) ($reverbApps[0] ?? []);
        $reverbOrigins = (array) ($reverb['allowed_origins'] ?? []);
        $reverbHost = (string) data_get($reverb, 'options.host', '');
        $viteOrigin = config('security.vite_dev_origin');
        $privateDisk = (array) config('filesystems.disks.private', []);

        $checks = [
            'APP_ENV' => config('app.env') === 'production',
            'APP_DEBUG' => config('app.debug') === false,
            'HTTPS_URL' => str_starts_with((string) config('app.url'), 'https://'),
            'APP_KEY' => filled(config('app.key')),
            'SESSION_COOKIE_SECURE' => config('session.secure') === true,
            'QUEUE_CONNECTION' => config('queue.default') !== 'sync',
            'PRIVATE_STORAGE' => $privateRoot !== '' && is_dir($privateRoot) && is_writable($privateRoot),
            'PRIVATE_STORAGE_DRIVER' => ($privateDisk['driver'] ?? null) === 's3',
            'REVERB_TLS' => (($reverb['options']['scheme'] ?? null) === 'https'),
            'REVERB_HOST' => $this->isPublicEndpoint($reverbHost),
            'REVERB_ORIGINS' => $reverbOrigins !== [] && ! in_array('*', $reverbOrigins, true),
            'VITE_DEV_SERVER_ORIGIN' => $viteOrigin === null || $this->isPublicHttpsEndpoint((string) $viteOrigin),
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
