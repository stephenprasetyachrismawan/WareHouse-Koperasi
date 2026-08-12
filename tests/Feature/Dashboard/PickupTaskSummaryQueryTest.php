<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Pickup\Queries\PickupTaskSummaryQuery;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupTaskSummaryQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_each_stage_correctly(): void
    {
        $warehouse = Warehouse::factory()->create();
        PickupRequest::factory()->for($warehouse)->submitted()->count(3)->create();
        PickupRequest::factory()->for($warehouse)->state(['status' => PickupRequestStatus::Backordered])->count(2)->create();
        PickupRequest::factory()->for($warehouse)->state(['status' => PickupRequestStatus::Checked])->count(1)->create();
        PickupRequest::factory()->for($warehouse)->readyForPickup()->count(4)->create();

        $result = app(PickupTaskSummaryQuery::class)->execute($warehouse->id);

        $this->assertSame([
            'new' => 3,
            'backordered' => 2,
            'toPrepare' => 1,
            'readyForFulfilment' => 4,
        ], $result);
    }

    public function test_terminal_and_irrelevant_statuses_are_excluded(): void
    {
        $warehouse = Warehouse::factory()->create();
        PickupRequest::factory()->for($warehouse)->completed()->count(5)->create();
        PickupRequest::factory()->for($warehouse)->cancelled()->count(5)->create();
        PickupRequest::factory()->for($warehouse)->approved()->count(5)->create();

        $result = app(PickupTaskSummaryQuery::class)->execute($warehouse->id);

        $this->assertSame(['new' => 0, 'backordered' => 0, 'toPrepare' => 0, 'readyForFulfilment' => 0], $result);
    }

    public function test_zero_state_when_no_pickups_exist(): void
    {
        $warehouse = Warehouse::factory()->create();

        $result = app(PickupTaskSummaryQuery::class)->execute($warehouse->id);

        $this->assertSame(['new' => 0, 'backordered' => 0, 'toPrepare' => 0, 'readyForFulfilment' => 0], $result);
    }

    public function test_never_counts_another_warehouses_pickups(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        PickupRequest::factory()->for($warehouseB)->submitted()->count(10)->create();

        $result = app(PickupTaskSummaryQuery::class)->execute($warehouseA->id);

        $this->assertSame(0, $result['new']);
    }
}
