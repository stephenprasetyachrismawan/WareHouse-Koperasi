<?php

namespace Tests\Feature\Procurement;

use App\Domain\Procurement\Queries\DetectDuplicatePurchaseRequestQuery;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetectDuplicatePurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_duplicate_when_in_progress(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        $pr = PurchaseRequest::factory()->draft()->create(['warehouse_id' => $warehouse->id]);
        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        $query = app(DetectDuplicatePurchaseRequestQuery::class);
        $result = $query->execute($warehouse->id, $item->id);

        $this->assertTrue($result['is_duplicate']);
        $this->assertEquals(10, $result['in_progress_qty']);
        $this->assertCount(1, $result['candidates']);
    }
}
