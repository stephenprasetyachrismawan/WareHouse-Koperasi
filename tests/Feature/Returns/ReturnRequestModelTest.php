<?php

namespace Tests\Feature\Returns;

use App\Enums\ReturnStatus;
use App\Models\ReturnEvidence;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_uuid_on_create(): void
    {
        $returnRequest = ReturnRequest::factory()->create();

        $this->assertNotNull($returnRequest->uuid);
    }

    public function test_it_has_relationships(): void
    {
        $returnRequest = ReturnRequest::factory()->create();
        $item = ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id]);
        $evidence = ReturnEvidence::factory()->create([
            'return_request_id' => $returnRequest->id,
            'warehouse_id' => $returnRequest->warehouse_id,
        ]);

        $this->assertNotNull($returnRequest->warehouse);
        $this->assertNotNull($returnRequest->cooperativeMembership);
        $this->assertNotNull($returnRequest->pickupRequest);
        $this->assertNotNull($returnRequest->submitter);
        $this->assertCount(1, $returnRequest->items);
        $this->assertCount(1, $returnRequest->evidence);
        $this->assertTrue($item->returnRequest->is($returnRequest));
        $this->assertTrue($evidence->returnRequest->is($returnRequest));
    }

    public function test_it_scopes_by_warehouse(): void
    {
        $warehouse1Return = ReturnRequest::factory()->create();
        $warehouse2Return = ReturnRequest::factory()->create();

        $returns = ReturnRequest::forWarehouse($warehouse1Return->warehouse_id)->get();

        $this->assertCount(1, $returns);
        $this->assertEquals($warehouse1Return->id, $returns->first()->id);
    }

    public function test_factory_states_produce_expected_statuses(): void
    {
        $this->assertEquals(ReturnStatus::Submitted, ReturnRequest::factory()->submitted()->create()->status);
        $this->assertEquals(ReturnStatus::AdminVerified, ReturnRequest::factory()->adminVerified()->create()->status);
        $this->assertEquals(ReturnStatus::WaitingApproval, ReturnRequest::factory()->waitingApproval()->create()->status);
    }
}
