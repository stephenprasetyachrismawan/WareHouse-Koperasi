<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\CreateReturnReplacementPurchaseRequestAction;
use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateReturnReplacementPurchaseRequestActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Item $item;

    private ReturnRequest $returnRequest;

    private ReturnRequestItem $returnRequestItem;

    private User $koperasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        $this->koperasi = User::factory()->create();

        $this->returnRequest = ReturnRequest::factory()->replacementPending()->create([
            'warehouse_id' => $this->warehouse->id,
            'submitted_by' => $this->koperasi->id,
        ]);
        $this->returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'item_id' => $this->item->id,
            'return_quantity' => 5,
        ]);
    }

    public function test_it_creates_a_draft_purchase_request_sourced_from_the_return(): void
    {
        $pr = app(CreateReturnReplacementPurchaseRequestAction::class)->execute($this->returnRequestItem, shortfallQuantity: 3);

        $this->assertEquals(PurchaseRequestSource::ReturnReplacement, $pr->source);
        $this->assertEquals(PurchaseRequestStatus::Draft, $pr->status);
        $this->assertEquals($this->warehouse->id, $pr->warehouse_id);
        $this->assertEquals($this->returnRequest->id, $pr->return_request_id);
        $this->assertEquals($this->koperasi->id, $pr->created_by);

        $this->assertCount(1, $pr->items);
        $this->assertEquals($this->item->id, $pr->items->first()->item_id);
        $this->assertEquals(3, $pr->items->first()->requested_quantity);
    }

    public function test_it_is_idempotent_for_the_same_return_item(): void
    {
        $action = app(CreateReturnReplacementPurchaseRequestAction::class);

        $first = $action->execute($this->returnRequestItem, shortfallQuantity: 3);
        $second = $action->execute($this->returnRequestItem, shortfallQuantity: 3);

        $this->assertEquals($first->id, $second->id);
        $this->assertSame(1, PurchaseRequest::where('return_request_id', $this->returnRequest->id)->count());
    }

    public function test_it_creates_a_new_one_after_the_previous_was_rejected(): void
    {
        $action = app(CreateReturnReplacementPurchaseRequestAction::class);

        $first = $action->execute($this->returnRequestItem, shortfallQuantity: 3);
        $first->update(['status' => PurchaseRequestStatus::Rejected]);

        $second = $action->execute($this->returnRequestItem, shortfallQuantity: 3);

        $this->assertNotEquals($first->id, $second->id);
        $this->assertSame(2, PurchaseRequest::where('return_request_id', $this->returnRequest->id)->count());
    }

    public function test_it_does_not_create_a_second_one_while_the_first_is_still_in_progress(): void
    {
        $action = app(CreateReturnReplacementPurchaseRequestAction::class);

        $action->execute($this->returnRequestItem, shortfallQuantity: 3);
        $action->execute($this->returnRequestItem, shortfallQuantity: 3);
        $action->execute($this->returnRequestItem, shortfallQuantity: 3);

        $this->assertSame(1, PurchaseRequest::where('return_request_id', $this->returnRequest->id)->count());
    }
}
