<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionConfigurationCommandTest extends TestCase
{
    use RefreshDatabase;

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

    private function configureValidProduction(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://warehouse.test',
            'session.secure' => true,
            'queue.default' => 'database',
            'security.vite_dev_origin' => 'https://vite.warehouse.test',
            'reverb.apps.apps.0.options.host' => 'realtime.warehouse.test',
            'reverb.apps.apps.0.options.scheme' => 'https',
            'reverb.apps.apps.0.allowed_origins' => ['https://warehouse.test'],
            'filesystems.disks.private.driver' => 's3',
        ]);
    }
}
