<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Returns\Queries\PendingReturnVerificationsQuery;
use App\Models\ReturnRequest;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingReturnVerificationsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_only_submitted_returns(): void
    {
        $warehouse = Warehouse::factory()->create();
        ReturnRequest::factory()->for($warehouse)->submitted()->count(3)->create();
        ReturnRequest::factory()->for($warehouse)->adminVerified()->count(2)->create();
        ReturnRequest::factory()->for($warehouse)->completed()->count(1)->create();

        $this->assertSame(3, app(PendingReturnVerificationsQuery::class)->count($warehouse->id));
    }

    public function test_never_counts_another_warehouses_returns(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        ReturnRequest::factory()->for($warehouseB)->submitted()->count(5)->create();

        $this->assertSame(0, app(PendingReturnVerificationsQuery::class)->count($warehouseA->id));
    }
}
