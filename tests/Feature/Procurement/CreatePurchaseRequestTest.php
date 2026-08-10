<?php

namespace Tests\Feature\Procurement;

use App\Domain\Procurement\Actions\CreatePurchaseRequestAction;
use App\Domain\Procurement\Exceptions\DuplicatePurchaseRequestException;
use App\Domain\Procurement\ValueObjects\PurchaseRequestInput;
use App\Domain\Procurement\ValueObjects\PurchaseRequestItemInput;
use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestUrgency;
use App\Events\Procurement\PurchaseRequestCreated;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreatePurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_purchase_request_and_dispatches_event(): void
    {
        Event::fake();

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouse->id, 'status' => 'active']);
        $item = Item::factory()->create();

        $input = new PurchaseRequestInput(
            warehouseId: $warehouse->id,
            userId: $user->id,
            source: PurchaseRequestSource::ManualStaff,
            urgency: PurchaseRequestUrgency::Normal,
            notes: 'Test notes',
            items: [
                new PurchaseRequestItemInput($item->id, 5, 'Item notes'),
            ]
        );

        $action = app(CreatePurchaseRequestAction::class);
        $pr = $action->execute($user, $input);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'warehouse_id' => $warehouse->id,
            'notes' => 'Test notes',
        ]);

        $this->assertDatabaseHas('purchase_request_items', [
            'purchase_request_id' => $pr->id,
            'item_id' => $item->id,
            'requested_quantity' => 5,
        ]);

        Event::assertDispatched(PurchaseRequestCreated::class);
    }

    public function test_it_throws_exception_on_duplicate_without_override(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouse->id, 'status' => 'active']);
        $item = Item::factory()->create();

        $pr = PurchaseRequest::factory()->draft()->create(['warehouse_id' => $warehouse->id]);
        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        $input = new PurchaseRequestInput(
            warehouseId: $warehouse->id,
            userId: $user->id,
            source: PurchaseRequestSource::ManualStaff,
            urgency: PurchaseRequestUrgency::Normal,
            notes: 'Test notes',
            items: [
                new PurchaseRequestItemInput($item->id, 5, 'Item notes'),
            ]
        );

        $action = app(CreatePurchaseRequestAction::class);

        $this->expectException(DuplicatePurchaseRequestException::class);
        $action->execute($user, $input);
    }
}
