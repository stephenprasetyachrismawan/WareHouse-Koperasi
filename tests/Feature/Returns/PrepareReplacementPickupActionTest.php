<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Enums\PickupRequestSource;
use App\Enums\PickupRequestStatus;
use App\Enums\PurchaseRequestSource;
use App\Enums\ReturnStatus;
use App\Models\Approval;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrepareReplacementPickupActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Item $item;

    private User $koperasi;

    private User $kepalaGudang;

    private ReturnRequest $returnRequest;

    private ReturnRequestItem $returnRequestItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);

        $this->koperasi = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $this->koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'active',
        ]);

        $this->kepalaGudang = User::factory()->create();

        $this->returnRequest = ReturnRequest::factory()->replacementPending()->create([
            'warehouse_id' => $this->warehouse->id,
            'cooperative_membership_id' => $membership->id,
            'submitted_by' => $this->koperasi->id,
            'approved_by' => $this->kepalaGudang->id,
        ]);
        $this->returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'item_id' => $this->item->id,
            'return_quantity' => 4,
        ]);
    }

    public function test_sufficient_stock_creates_a_ready_replacement_pickup(): void
    {
        StockBalance::factory()->create(['warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id, 'quantity' => 10]);

        $result = app(PrepareReplacementPickupAction::class)->execute($this->returnRequest);

        $this->assertEquals(ReturnStatus::ReadyForRepickup, $result->status);
        $this->assertNotNull($result->replacement_pickup_request_id);

        $pickup = PickupRequest::find($result->replacement_pickup_request_id);
        $this->assertEquals(PickupRequestSource::ReturnReplacement, $pickup->source);
        $this->assertEquals(PickupRequestStatus::ReadyForPickup, $pickup->status);
        $this->assertEquals($this->koperasi->id, $pickup->user_id);
        $this->assertNotNull($pickup->ready_at);

        $this->assertCount(1, $pickup->items);
        $this->assertEquals($this->item->id, $pickup->items->first()->item_id);
        $this->assertEquals(4, $pickup->items->first()->requested_quantity);

        $approval = Approval::where('approvable_type', PickupRequest::class)
            ->where('approvable_id', $pickup->id)
            ->first();
        $this->assertNotNull($approval);
        $this->assertEquals('AUTO_APPROVED', $approval->status->value);

        $this->assertSame(0, PurchaseRequest::count());

        // No stock deducted merely from preparing/scheduling.
        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('item_id', $this->item->id)->first();
        $this->assertEquals(10, $balance->quantity);
    }

    public function test_insufficient_stock_creates_a_return_replacement_purchase_request_and_stays_pending(): void
    {
        StockBalance::factory()->create(['warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id, 'quantity' => 1]);

        $result = app(PrepareReplacementPickupAction::class)->execute($this->returnRequest);

        $this->assertEquals(ReturnStatus::ReplacementPending, $result->status);
        $this->assertNull($result->replacement_pickup_request_id);

        $pr = PurchaseRequest::where('return_request_id', $this->returnRequest->id)->first();
        $this->assertNotNull($pr);
        $this->assertEquals(PurchaseRequestSource::ReturnReplacement, $pr->source);
        $this->assertEquals(3, $pr->items->first()->requested_quantity);

        $this->assertSame(0, PickupRequest::where('source', PickupRequestSource::ReturnReplacement->value)->count());
    }

    public function test_it_is_idempotent_once_ready(): void
    {
        StockBalance::factory()->create(['warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id, 'quantity' => 10]);

        $action = app(PrepareReplacementPickupAction::class);
        $first = $action->execute($this->returnRequest);
        $second = $action->execute($first->fresh());

        $this->assertEquals($first->replacement_pickup_request_id, $second->replacement_pickup_request_id);
        $this->assertSame(1, PickupRequest::where('source', PickupRequestSource::ReturnReplacement->value)->count());
    }

    public function test_it_does_not_prepare_a_return_that_is_not_replacement_pending(): void
    {
        $this->returnRequest->update(['status' => ReturnStatus::WaitingApproval]);

        $result = app(PrepareReplacementPickupAction::class)->execute($this->returnRequest->fresh());

        $this->assertEquals(ReturnStatus::WaitingApproval, $result->status);
        $this->assertNull($result->replacement_pickup_request_id);
    }
}
