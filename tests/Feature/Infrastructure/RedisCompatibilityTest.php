<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Proves the application can actually use Redis for cache and queue, not
 * just that a Redis service container starts. Deliberately isolated from
 * the rest of the suite: most of the suite (phpunit.xml) assumes
 * QUEUE_CONNECTION=sync so job side effects are observable immediately
 * after the triggering call, an assumption a real async Redis queue
 * doesn't satisfy. These tests explicitly target the "redis" connection
 * regardless of the suite-wide default, so they can run truthfully in a
 * CI lane that keeps the rest of the suite on its normal sync/array
 * config while still genuinely exercising Redis.
 */
class RedisCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Skipped, never faked as passing, when no Redis server is reachable
        // -- e.g. the SQLite-only "quality" CI lane and local development on
        // this VPS, neither of which run a Redis service. The dedicated
        // "integration" CI job (real ephemeral Redis service container) is
        // the environment this test is meant to run in.
        try {
            Cache::store('redis')->getStore()->connection()->ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Redis is not reachable in this environment: '.$e->getMessage());
        }
    }

    public function test_redis_cache_store_round_trips(): void
    {
        Cache::store('redis')->put('ci-redis-smoke-cache', 'ok', 10);

        $this->assertSame('ok', Cache::store('redis')->get('ci-redis-smoke-cache'));

        Cache::store('redis')->forget('ci-redis-smoke-cache');
        $this->assertNull(Cache::store('redis')->get('ci-redis-smoke-cache'));
    }

    public function test_redis_queue_actually_pushes_and_processes_a_job(): void
    {
        Cache::store('redis')->forget('ci-redis-smoke-job');

        $job = CallQueuedClosure::create(function () {
            Cache::store('redis')->put('ci-redis-smoke-job', 'processed', 30);
        });

        Queue::connection('redis')->push($job);

        $popped = Queue::connection('redis')->pop();
        $this->assertNotNull($popped, 'expected a job to be poppable from the redis queue connection');

        $popped->fire();

        $this->assertSame('processed', Cache::store('redis')->get('ci-redis-smoke-job'));

        Cache::store('redis')->forget('ci-redis-smoke-job');
    }
}
