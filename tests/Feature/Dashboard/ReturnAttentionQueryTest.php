<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Returns\Queries\ReturnAttentionQuery;
use App\Models\ReturnRequest;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnAttentionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_replacement_pending_returns_only(): void
    {
        $warehouse = Warehouse::factory()->create();
        ReturnRequest::factory()->for($warehouse)->replacementPending()->count(2)->create();
        ReturnRequest::factory()->for($warehouse)->completed()->count(3)->create();

        $this->assertSame(2, app(ReturnAttentionQuery::class)->replacementPendingCount($warehouse->id));
    }

    public function test_never_counts_another_warehouse(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        ReturnRequest::factory()->for($warehouseB)->replacementPending()->create();

        $this->assertSame(0, app(ReturnAttentionQuery::class)->replacementPendingCount($warehouseA->id));
    }
}
