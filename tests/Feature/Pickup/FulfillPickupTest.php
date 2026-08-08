<?php

namespace Tests\Feature\Pickup;

use App\Actions\Pickup\FulfillPickupAction;
use App\Domain\Inventory\Exceptions\DuplicateStockMovementException;
use App\Domain\Pickup\Events\PickupRequestCompleted;
use App\Enums\Permission;
use App\Enums\PickupRequestStatus;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FulfillPickupTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fulfill_pickup_request_and_deduct_stock()
    {
        Event::fake();

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => 'staff_admin',
            'status' => 'active',
            'permissions' => [Permission::PickupRequestFulfill->value],
        ]);

        $item = Item::factory()->create();
        StockBalance::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 100,
            'version' => 1,
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::ReadyForPickup,
        ]);

        $line = PickupRequestItem::factory()->create([
            'pickup_request_id' => $pickupRequest->id,
            'item_id' => $item->id,
            'requested_quantity' => 10,
            'fulfilled_quantity' => 0,
        ]);

        $action = app(FulfillPickupAction::class);
        $result = $action->execute($user, $pickupRequest);

        $this->assertEquals(PickupRequestStatus::Completed, $result->status);
        $this->assertNotNull($result->completed_at);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 90,
            'version' => 2,
        ]);

        $this->assertDatabaseHas('pickup_request_items', [
            'id' => $line->id,
            'fulfilled_quantity' => 10,
        ]);

        Event::assertDispatched(PickupRequestCompleted::class, function ($event) use ($pickupRequest) {
            return $event->pickupRequest->id === $pickupRequest->id;
        });
    }

    public function test_rollback_on_inventory_failure()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => 'staff_admin',
            'status' => 'active',
            'permissions' => [Permission::PickupRequestFulfill->value],
        ]);

        $item = Item::factory()->create();
        StockBalance::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 5,
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::ReadyForPickup,
        ]);

        $line = PickupRequestItem::factory()->create([
            'pickup_request_id' => $pickupRequest->id,
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        // Pre-create a transaction with the same idempotency key to cause DuplicateStockMovementException
        StockTransaction::factory()->create([
            'warehouse_id' => $warehouse->id,
            'idempotency_key' => "pickup-fulfill-{$pickupRequest->id}-{$line->id}",
        ]);

        $action = app(FulfillPickupAction::class);

        try {
            $action->execute($user, $pickupRequest);
        } catch (DuplicateStockMovementException $e) {
            // expected
        }

        $this->assertDatabaseHas('pickup_requests', [
            'id' => $pickupRequest->id,
            'status' => PickupRequestStatus::ReadyForPickup->value,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 5,
        ]);
    }

    public function test_double_submit_idempotency()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => 'staff_admin',
            'status' => 'active',
            'permissions' => [Permission::PickupRequestFulfill->value],
        ]);

        $item = Item::factory()->create();
        StockBalance::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::ReadyForPickup,
        ]);

        $line = PickupRequestItem::factory()->create([
            'pickup_request_id' => $pickupRequest->id,
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        $action = app(FulfillPickupAction::class);
        $action->execute($user, $pickupRequest);

        // 2nd time should just return if status is completed
        $result = $action->execute($user, $pickupRequest);

        $this->assertEquals(PickupRequestStatus::Completed, $result->status);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 90,
        ]);
    }
}
