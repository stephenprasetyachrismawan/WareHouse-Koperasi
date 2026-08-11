<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\RecordReturnDisposalAction;
use App\Models\Item;
use App\Models\ReturnDisposal;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordReturnDisposalActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_disposal_trace_links_return_item_and_quantity(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        $actor = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
            'return_quantity' => 7,
        ]);

        $disposal = app(RecordReturnDisposalAction::class)->execute($actor, $returnRequestItem);

        $this->assertEquals($returnRequest->id, $disposal->return_request_id);
        $this->assertEquals($returnRequestItem->id, $disposal->return_request_item_id);
        $this->assertEquals($item->id, $disposal->item_id);
        $this->assertEquals($warehouse->id, $disposal->warehouse_id);
        $this->assertEquals(7, $disposal->quantity);
        $this->assertEquals($actor->id, $disposal->disposed_by);
    }

    public function test_calling_it_twice_directly_produces_only_one_disposal(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        $actor = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
        ]);

        $action = app(RecordReturnDisposalAction::class);
        $first = $action->execute($actor, $returnRequestItem);
        $second = $action->execute($actor, $returnRequestItem);

        $this->assertEquals($first->id, $second->id);
        $this->assertSame(1, ReturnDisposal::count());
    }
}
