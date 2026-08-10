<?php

namespace Tests\Feature\Procurement;

use App\Domain\Pickup\Events\StockShortageDetected;
use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3BackorderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_purchase_request_on_stock_shortage()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
        ]);
        $item = Item::factory()->create();

        event(new StockShortageDetected($pickupRequest, $item, 10, 5));

        $this->assertDatabaseHas('purchase_requests', [
            'warehouse_id' => $warehouse->id,
            'source' => PurchaseRequestSource::CooperativeBackorder->value,
            'status' => PurchaseRequestStatus::Draft->value,
            'created_by' => $user->id,
            'pickup_request_id' => $pickupRequest->id,
        ]);

        $pr = PurchaseRequest::where('pickup_request_id', $pickupRequest->id)->first();

        $this->assertDatabaseHas('purchase_request_items', [
            'purchase_request_id' => $pr->id,
            'item_id' => $item->id,
            'requested_quantity' => 5,
        ]);
    }

    public function test_it_is_idempotent()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
        ]);
        $item = Item::factory()->create();

        // Trigger first time
        event(new StockShortageDetected($pickupRequest, $item, 10, 5));

        // Trigger second time
        event(new StockShortageDetected($pickupRequest, $item, 10, 5));

        $this->assertEquals(1, PurchaseRequest::where('pickup_request_id', $pickupRequest->id)->count());
    }
}
