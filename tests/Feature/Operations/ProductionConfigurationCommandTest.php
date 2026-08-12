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
        $result = $this->artisan('ops:validate-production');

        $result->expectsOutputToContain('APP_ENV=FAIL')
            ->expectsOutputToContain('APP_DEBUG=FAIL')
            ->expectsOutputToContain('Production configuration is not ready.')
            ->assertExitCode(1);
    }

    public function test_valid_production_configuration_passes_the_safe_checks(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://warehouse.test',
            'session.secure' => true,
            'queue.default' => 'database',
            'reverb.apps.apps.0.options.scheme' => 'https',
            'reverb.apps.apps.0.allowed_origins' => ['https://warehouse.test'],
        ]);

        $this->artisan('ops:validate-production')
            ->expectsOutputToContain('Production configuration is ready.')
            ->assertExitCode(0);
    }
}
