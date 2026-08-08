<?php

use App\Actions\Pickup\SubmitPickupRequestAction;
use App\Domain\Pickup\Events\PickupRequestSubmitted;
use App\Domain\Pickup\Events\StockShortageDetected;
use App\Enums\PickupRequestStatus;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('submits pickup request without shortage', function () {
    Event::fake([PickupRequestSubmitted::class, StockShortageDetected::class]);

    $user = User::factory()->create();
    $pickupRequest = PickupRequest::factory()->create([
        'status' => PickupRequestStatus::Draft, // Assuming it started as draft or something before submit
    ]);

    $item = Item::factory()->create();
    StockBalance::factory()->create([
        'item_id' => $item->id,
        'quantity' => 10,
    ]);

    $pickupRequest->items()->create([
        'item_id' => $item->id,
        'requested_quantity' => 5,
    ]);

    $action = app(SubmitPickupRequestAction::class);
    $result = $action->execute($user, $pickupRequest);

    expect($result->status)->toBe(PickupRequestStatus::WaitingApproval);
    expect($result->items->first()->shortage_quantity)->toBe(0);

    Event::assertDispatched(PickupRequestSubmitted::class);
    Event::assertNotDispatched(StockShortageDetected::class);
});

it('submits pickup request with shortage', function () {
    Event::fake([PickupRequestSubmitted::class, StockShortageDetected::class]);

    $user = User::factory()->create();
    $pickupRequest = PickupRequest::factory()->create([
        'status' => PickupRequestStatus::Draft,
    ]);

    $item = Item::factory()->create();
    StockBalance::factory()->create([
        'item_id' => $item->id,
        'quantity' => 3,
    ]);

    $pickupRequest->items()->create([
        'item_id' => $item->id,
        'requested_quantity' => 5,
    ]);

    $action = app(SubmitPickupRequestAction::class);
    $result = $action->execute($user, $pickupRequest);

    expect($result->status)->toBe(PickupRequestStatus::Backordered);
    expect($result->items->first()->shortage_quantity)->toBe(2);

    Event::assertDispatched(PickupRequestSubmitted::class);
    Event::assertDispatched(StockShortageDetected::class);
});
