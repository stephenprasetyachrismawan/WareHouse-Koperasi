<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CreatePurchaseRequestGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_it_creates_a_group_with_allocations_from_approved_requests(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 10,
        ]);

        $input = new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: 'Batch 1',
            allocations: [new AllocationInput($item->id, 6)],
        );

        $action = app(CreatePurchaseRequestGroupAction::class);
        $group = $action->execute($user, $input);

        $this->assertNotNull($group->group_number);
        $this->assertStringStartsWith('PRG-', $group->group_number);

        $this->assertDatabaseHas('purchase_request_allocations', [
            'purchase_request_group_id' => $group->id,
            'purchase_request_item_id' => $item->id,
            'allocated_quantity' => 6,
            'allocated_by' => $user->id,
        ]);
    }

    public function test_it_generates_a_group_number_without_locking_an_aggregate_query(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 1,
        ]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: 'PostgreSQL compatibility',
            allocations: [new AllocationInput($item->id, 1)],
        ));

        $this->assertMatchesRegularExpression('/^PRG-\\d{8}-\\d{4}$/', $group->group_number);
    }

    public function test_it_rejects_allocation_exceeding_remaining_quantity(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 5,
        ]);

        $input = new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($item->id, 999)],
        );

        $this->expectException(\Exception::class);

        app(CreatePurchaseRequestGroupAction::class)->execute($user, $input);
    }

    public function test_it_rejects_allocation_from_non_approved_request(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseRequest = PurchaseRequest::factory()->draft()->create(['warehouse_id' => $warehouse->id]);
        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 5,
        ]);

        $input = new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($item->id, 1)],
        );

        $this->expectException(\Exception::class);

        app(CreatePurchaseRequestGroupAction::class)->execute($user, $input);
    }

    public function test_it_prevents_double_allocation_beyond_remaining_across_groups(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $purchaseRequest = PurchaseRequest::factory()->approved()->create(['warehouse_id' => $warehouse->id]);
        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'requested_quantity' => 10,
        ]);

        app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($item->id, 7)],
        ));

        $this->expectException(\Exception::class);

        app(CreatePurchaseRequestGroupAction::class)->execute($user, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: null,
            allocations: [new AllocationInput($item->id, 5)],
        ));
    }
}
