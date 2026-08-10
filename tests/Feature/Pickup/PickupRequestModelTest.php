<?php

namespace Tests\Feature\Pickup;

use App\Enums\PickupRequestStatus;
use App\Models\Approval;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_uuid_on_boot(): void
    {
        $pickupRequest = PickupRequest::factory()->create();

        $this->assertNotNull($pickupRequest->uuid);
    }

    public function test_it_has_relationships(): void
    {
        $pickupRequest = PickupRequest::factory()->create();
        $item = PickupRequestItem::factory()->create(['pickup_request_id' => $pickupRequest->id]);
        $approval = Approval::factory()->create([
            'approvable_type' => PickupRequest::class,
            'approvable_id' => $pickupRequest->id,
            'warehouse_id' => $pickupRequest->warehouse_id,
        ]);

        $this->assertNotNull($pickupRequest->warehouse);
        $this->assertNotNull($pickupRequest->user);
        $this->assertCount(1, $pickupRequest->items);
        $this->assertCount(1, $pickupRequest->approvals);
    }

    public function test_it_scopes_by_warehouse(): void
    {
        $warehouse1Request = PickupRequest::factory()->create();
        $warehouse2Request = PickupRequest::factory()->create();

        $requests = PickupRequest::forWarehouse($warehouse1Request->warehouse_id)->get();

        $this->assertCount(1, $requests);
        $this->assertEquals($warehouse1Request->id, $requests->first()->id);
    }

    public function test_it_knows_if_terminal(): void
    {
        $pickupRequest = PickupRequest::factory()->create(['status' => PickupRequestStatus::Draft]);
        $this->assertFalse($pickupRequest->isTerminal());

        $pickupRequest->update(['status' => PickupRequestStatus::Completed]);
        $this->assertTrue($pickupRequest->isTerminal());
    }
}
