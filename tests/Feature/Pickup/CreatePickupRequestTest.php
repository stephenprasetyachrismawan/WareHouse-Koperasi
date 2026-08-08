<?php

use App\Actions\Pickup\CreatePickupRequestAction;
use App\Domain\Pickup\ValueObjects\PickupRequestInput;
use App\Domain\Pickup\ValueObjects\PickupRequestItemInput;
use App\Enums\PickupRequestStatus;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;

it('creates a pickup request successfully', function () {
    $warehouse = Warehouse::factory()->create();
    $user = User::factory()->create();

    WarehouseMembership::factory()->create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'active',
    ]);

    $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

    $action = app(CreatePickupRequestAction::class);

    $input = new PickupRequestInput(
        warehouseId: $warehouse->id,
        userId: $user->id,
        notes: 'Please prepare soon',
        items: [
            new PickupRequestItemInput(itemId: $item->id, quantity: 5, notes: 'urgent'),
        ]
    );

    $pickupRequest = $action->execute($user, $input);

    expect($pickupRequest)->toBeInstanceOf(PickupRequest::class)
        ->and($pickupRequest->warehouse_id)->toBe($warehouse->id)
        ->and($pickupRequest->user_id)->toBe($user->id)
        ->and($pickupRequest->status)->toBe(PickupRequestStatus::Submitted)
        ->and($pickupRequest->notes)->toBe('Please prepare soon')
        ->and($pickupRequest->request_number)->toStartWith('REQ-');

    expect($pickupRequest->items)->toHaveCount(1)
        ->first()->item_id->toBe($item->id)
        ->first()->requested_quantity->toBe(5)
        ->first()->notes->toBe('urgent');
});

it('fails if user is not an active member', function () {
    $warehouse = Warehouse::factory()->create();
    $user = User::factory()->create();
    $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

    $action = app(CreatePickupRequestAction::class);

    $input = new PickupRequestInput(
        warehouseId: $warehouse->id,
        userId: $user->id,
        items: [
            new PickupRequestItemInput(itemId: $item->id, quantity: 5),
        ]
    );

    $this->expectException(AuthorizationException::class);
    $action->execute($user, $input);
});
