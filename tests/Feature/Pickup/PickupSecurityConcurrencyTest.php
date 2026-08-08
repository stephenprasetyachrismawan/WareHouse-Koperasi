<?php

namespace Tests\Feature\Pickup;

use App\Actions\Pickup\CreatePickupRequestAction;
use App\Actions\Pickup\FulfillPickupAction;
use App\Domain\Pickup\ValueObjects\PickupRequestInput;
use App\Domain\Pickup\ValueObjects\PickupRequestItemInput;
use App\Enums\PickupRequestStatus;
use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupSecurityConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Warehouse $warehouse;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->user = User::factory()->create();

        $this->user->warehouseMemberships()->create([
            'warehouse_id' => $this->warehouse->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->item = Item::factory()->create();
        $this->item->stockBalance()->create([
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);

        session(['tenant_id' => $this->warehouse->id]);
    }

    public function test_tenant_isolation_user_cannot_create_for_other_warehouse()
    {
        $otherWarehouse = Warehouse::factory()->create();

        $createAction = new CreatePickupRequestAction;

        $input = new PickupRequestInput(
            $otherWarehouse->id,
            $this->user->id,
            'Test',
            [new PickupRequestItemInput($this->item->id, 5)]
        );

        $this->expectException(AuthorizationException::class);
        $createAction->execute($this->user, $input);
    }

    public function test_double_submit_idempotency_prevents_duplicate_deduction()
    {
        $request = PickupRequest::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'status' => PickupRequestStatus::Approved,
        ]);

        $request->items()->create([
            'item_id' => $this->item->id,
            'requested_quantity' => 5,
        ]);

        $fulfillAction = app(FulfillPickupAction::class);

        // Ensure user has membership and roles so Fulfill works
        $this->user->warehouseMemberships()->updateOrCreate(
            ['warehouse_id' => $this->warehouse->id],
            ['role' => WarehouseRole::StaffAdmin->value, 'status' => 'active']
        );

        // First fulfillment should succeed
        $fulfillAction->execute($this->user, $request);
        $this->assertEquals(5, $this->item->stockBalance()->first()->quantity);
        $this->assertEquals(PickupRequestStatus::Completed, $request->fresh()->status);

        // Second fulfillment should safely return the already completed request without re-deducting
        $result = $fulfillAction->execute($this->user, $request);
        $this->assertEquals(PickupRequestStatus::Completed, $result->status);

        // Stock should still be 5
        $this->assertEquals(5, $this->item->stockBalance()->first()->quantity);
    }

    public function test_mass_assignment_protection_via_input_dto()
    {
        // The action takes a strict DTO, preventing arbitrary mass assignment from request payload
        $input = new PickupRequestInput(
            $this->warehouse->id,
            $this->user->id,
            'Test Notes',
            [new PickupRequestItemInput($this->item->id, 5)]
        );

        $createAction = new CreatePickupRequestAction;
        $request = $createAction->execute($this->user, $input);

        // Even though user might try to pass status, the DTO doesn't accept it,
        // and the Action hardcodes initial status, ensuring protection.
        $this->assertEquals(PickupRequestStatus::Submitted, $request->status);
    }
}
