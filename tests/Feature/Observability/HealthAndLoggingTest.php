<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class HealthAndLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_is_public_minimal_and_does_not_expose_runtime_details(): void
    {
        $this->get('/health/live')
            ->assertOk()
            ->assertExactJson(['status' => 'ok'])
            ->assertHeader('X-Request-Id')
            ->assertJsonMissingPath('database')
            ->assertJsonMissingPath('environment');
    }

    public function test_readiness_checks_the_database_and_returns_safe_output(): void
    {
        $this->get('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'ready'])
            ->assertJsonMissingPath('connection')
            ->assertJsonMissingPath('exception');
    }

    public function test_http_request_log_contains_safe_correlation_and_outcome_context(): void
    {
        Log::spy();

        $this->get('/health/live', ['X-Request-Id' => 'obs-request-001'])->assertOk();

        Log::shouldHaveReceived('info')
            ->with('http.request', Mockery::on(function (array $context): bool {
                return $context['request_id'] === 'obs-request-001'
                    && $context['status'] === 200
                    && array_key_exists('latency_ms', $context)
                    && ! array_key_exists('request_body', $context);
            }))
            ->once();
    }
}
