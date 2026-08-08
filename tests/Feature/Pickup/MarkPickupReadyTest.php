<?php

namespace Tests\Feature\Pickup;

use App\Actions\Pickup\MarkPickupReadyAction;
use App\Domain\Pickup\Events\PickupRequestReadyForPickup;
use App\Enums\Permission;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MarkPickupReadyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_mark_approved_pickup_as_ready()
    {
        Event::fake();

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => 'staff_admin',
            'status' => 'active',
            'permissions' => [Permission::PickupRequestPrepare->value],
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::Approved,
        ]);

        $action = app(MarkPickupReadyAction::class);
        $result = $action->execute($user, $pickupRequest);

        $this->assertEquals(PickupRequestStatus::ReadyForPickup, $result->status);
        $this->assertNotNull($result->ready_at);
        $this->assertDatabaseHas('pickup_requests', [
            'id' => $pickupRequest->id,
            'status' => PickupRequestStatus::ReadyForPickup->value,
        ]);

        Event::assertDispatched(PickupRequestReadyForPickup::class, function ($event) use ($pickupRequest) {
            return $event->pickupRequest->id === $pickupRequest->id;
        });
    }

    public function test_cannot_mark_ready_if_unauthorized()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => 'staff',
            'status' => 'active',
            'permissions' => [],
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::Approved,
        ]);

        $action = app(MarkPickupReadyAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($user, $pickupRequest);
    }

    public function test_cannot_mark_ready_from_invalid_status()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => 'staff_admin',
            'status' => 'active',
            'permissions' => [Permission::PickupRequestPrepare->value],
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::Draft,
        ]);

        $action = app(MarkPickupReadyAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($user, $pickupRequest);
    }
}
