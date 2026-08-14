<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Tests\TestCase;

class ProductionConfigurationCommandTest extends TestCase
{
    public function test_local_debug_configuration_is_rejected_without_printing_secret_values(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
        ]);

        $result = $this->artisan('ops:validate-production');

        $result->expectsOutputToContain('APP_ENV=FAIL')
            ->expectsOutputToContain('APP_DEBUG=FAIL')
            ->expectsOutputToContain('Production configuration is not ready.')
            ->assertExitCode(1);
    }

    public function test_valid_production_configuration_passes_the_safe_checks(): void
    {
        $this->configureValidProduction();

        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('Production configuration is ready.')
            ->assertExitCode(0);
    }

    public function test_local_vite_and_reverb_endpoints_are_rejected_in_production(): void
    {
        $this->configureValidProduction();
        config([
            'security.vite_dev_origin' => 'http://localhost:5173',
            'reverb.apps.apps.0.options.host' => 'localhost',
            'security.vite_reverb_host' => 'localhost',
        ]);
        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('VITE_DEV_SERVER_ORIGIN=FAIL')
            ->expectsOutputToContain('REVERB_HOST=FAIL')
            ->expectsOutputToContain('Production configuration is not ready.')
            ->assertExitCode(1);
    }

    public function test_local_private_storage_driver_is_rejected_in_production(): void
    {
        $this->configureValidProduction();
        config(['filesystems.disks.private.driver' => 'local']);

        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('PRIVATE_STORAGE_DRIVER=FAIL')
            ->assertExitCode(1);
    }

    public function test_local_frontend_reverb_host_is_rejected_in_production(): void
    {
        $this->configureValidProduction();
        config(['security.vite_reverb_host' => 'localhost']);

        $this->artisan('ops:validate-production')
            ->assertExitCode(1);
    }

    public function test_sqlite_and_non_tls_database_are_rejected_in_production(): void
    {
        $this->configureValidProduction();
        config([
            'database.default' => 'sqlite',
            'database.connections.pgsql.sslmode' => 'prefer',
        ]);

        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('DB_CONNECTION=FAIL')
            ->expectsOutputToContain('DB_TLS=FAIL')
            ->assertExitCode(1);
    }

    public function test_database_queue_cache_and_session_are_rejected_in_production(): void
    {
        $this->configureValidProduction();
        config([
            'queue.default' => 'database',
            'cache.default' => 'database',
            'session.driver' => 'database',
        ]);

        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('QUEUE_CONNECTION=FAIL')
            ->expectsOutputToContain('CACHE_STORE=FAIL')
            ->expectsOutputToContain('SESSION_DRIVER=FAIL')
            ->assertExitCode(1);
    }

    public function test_production_requires_private_s3_bucket_and_explicit_realtime_credentials(): void
    {
        $this->configureValidProduction();
        config([
            'filesystems.default' => 'local',
            'filesystems.disks.private.bucket' => '',
            'filesystems.disks.private.visibility' => 'public',
            'broadcasting.default' => 'log',
            'reverb.apps.apps.0.key' => '',
            'reverb.apps.apps.0.secret' => '',
            'reverb.apps.apps.0.app_id' => '',
        ]);

        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('FILESYSTEM_DISK=FAIL')
            ->expectsOutputToContain('PRIVATE_STORAGE=FAIL')
            ->expectsOutputToContain('BROADCAST_CONNECTION=FAIL')
            ->expectsOutputToContain('REVERB_APP_CREDENTIALS=FAIL')
            ->assertExitCode(1);
    }

    public function test_reverb_origins_must_be_pinned_to_the_canonical_application_origin(): void
    {
        $this->configureValidProduction();
        config([
            'reverb.apps.apps.0.allowed_origins' => ['https://another.example'],
        ]);

        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('REVERB_ORIGINS=FAIL')
            ->assertExitCode(1);
    }

    private function configureValidProduction(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://warehouse.test',
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'verify-full',
            'database.redis.default.url' => 'rediss://redis.example.test:6380',
            'session.secure' => true,
            'session.driver' => 'redis',
            'cache.default' => 'redis',
            'queue.default' => 'redis',
            'filesystems.default' => 's3',
            'filesystems.disks.private.bucket' => 'warehouse-private',
            'filesystems.disks.private.visibility' => 'private',
            'broadcasting.default' => 'reverb',
            'security.vite_dev_origin' => null,
            'security.vite_reverb_host' => 'realtime.warehouse.test',
            'security.vite_reverb_scheme' => 'https',
            'reverb.apps.apps.0.options.host' => 'realtime.warehouse.test',
            'reverb.apps.apps.0.options.scheme' => 'https',
            'reverb.apps.apps.0.allowed_origins' => ['https://warehouse.test'],
            'reverb.apps.apps.0.key' => 'public-key-for-test',
            'reverb.apps.apps.0.secret' => 'secret-for-test',
            'reverb.apps.apps.0.app_id' => 'app-id-for-test',
            'filesystems.disks.private.driver' => 's3',
        ]);
    }
}
