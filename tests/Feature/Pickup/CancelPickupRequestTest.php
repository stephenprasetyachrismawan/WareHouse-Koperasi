<?php

namespace Tests\Feature\Pickup;

use App\Actions\Pickup\CancelPickupRequestAction;
use App\Domain\Pickup\Events\PickupRequestCancelled;
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

class CancelPickupRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_cancel_pickup_request()
    {
        Event::fake();

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
            'permissions' => [Permission::PickupRequestCancel->value],
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::Draft,
        ]);

        $action = app(CancelPickupRequestAction::class);
        $result = $action->execute($user, $pickupRequest, 'Not needed anymore');

        $this->assertEquals(PickupRequestStatus::Cancelled, $result->status);
        $this->assertNotNull($result->cancelled_at);
        $this->assertEquals('Not needed anymore', $result->cancellation_reason);

        $this->assertDatabaseHas('pickup_requests', [
            'id' => $pickupRequest->id,
            'status' => PickupRequestStatus::Cancelled->value,
            'cancellation_reason' => 'Not needed anymore',
        ]);

        Event::assertDispatched(PickupRequestCancelled::class, function ($event) use ($pickupRequest) {
            return $event->pickupRequest->id === $pickupRequest->id
                && $event->reason === 'Not needed anymore';
        });
    }

    public function test_cannot_cancel_if_unauthorized()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
            'permissions' => [],
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::Draft,
        ]);

        $action = app(CancelPickupRequestAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($user, $pickupRequest, 'Reason');
    }

    public function test_cannot_cancel_terminal_status()
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
            'permissions' => [Permission::PickupRequestCancel->value],
        ]);

        $pickupRequest = PickupRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PickupRequestStatus::Completed,
        ]);

        $action = app(CancelPickupRequestAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($user, $pickupRequest, 'Reason');
    }
}
