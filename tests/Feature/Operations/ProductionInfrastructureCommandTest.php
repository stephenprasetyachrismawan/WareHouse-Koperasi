<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionInfrastructureCommandTest extends TestCase
{
    public function test_default_probe_checks_configuration_database_and_redis_without_writing_business_data(): void
    {
        $this->configureValidProductionForProbe();
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([]);
        Redis::shouldReceive('connection')->once()->with('default')->andReturnSelf();
        Redis::shouldReceive('ping')->once()->andReturn('PONG');

        $this->artisan('ops:verify-production-infrastructure')
            ->expectsOutputToContain('DATABASE=PASS')
            ->expectsOutputToContain('REDIS=PASS')
            ->expectsOutputToContain('PRIVATE_STORAGE=SKIPPED')
            ->assertExitCode(0);
    }

    public function test_storage_probe_requires_explicit_confirmation_before_writing(): void
    {
        $this->configureValidProductionForProbe();
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([]);
        Redis::shouldReceive('connection')->once()->with('default')->andReturnSelf();
        Redis::shouldReceive('ping')->once()->andReturn('PONG');

        $this->artisan('ops:verify-production-infrastructure', ['--storage-smoke' => true])
            ->expectsOutputToContain('PRIVATE_STORAGE=FAIL')
            ->assertExitCode(1);
    }

    public function test_confirmed_storage_probe_uses_private_disk_and_cleans_up_probe_object(): void
    {
        $this->configureValidProductionForProbe();
        Storage::fake('private');
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([]);
        Redis::shouldReceive('connection')->once()->with('default')->andReturnSelf();
        Redis::shouldReceive('ping')->once()->andReturn('PONG');

        $this->artisan('ops:verify-production-infrastructure', [
            '--storage-smoke' => true,
            '--confirm-storage-smoke' => true,
        ])
            ->expectsOutputToContain('PRIVATE_STORAGE=PASS')
            ->assertExitCode(0);

        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_invalid_production_configuration_stops_infrastructure_probe(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
        ]);

        $this->artisan('ops:verify-production-infrastructure')
            ->expectsOutputToContain('CONFIGURATION=FAIL')
            ->assertExitCode(1);
    }

    private function configureValidProductionForProbe(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://warehouse.test',
            'app.key' => 'base64:'.base64_encode('phase-6-4e-test-key'),
            'database.default' => 'pgsql',
            'database.connections.sqlite.database' => database_path('database.sqlite'),
            'database.connections.pgsql.sslmode' => 'verify-full',
            'database.redis.default.url' => 'rediss://redis.example.test:6380',
            'session.secure' => true,
            'session.driver' => 'redis',
            'cache.default' => 'redis',
            'queue.default' => 'redis',
            'filesystems.default' => 's3',
            'filesystems.disks.private.driver' => 's3',
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
        ]);
    }
}
