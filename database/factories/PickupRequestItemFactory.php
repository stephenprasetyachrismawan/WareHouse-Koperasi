<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupRequestItemFactory extends Factory
{
    protected $model = PickupRequestItem::class;

    public function definition(): array
    {
        return [
            'pickup_request_id' => PickupRequest::factory(),
            'item_id' => Item::factory(),
            'requested_quantity' => $this->faker->numberBetween(1, 100),
            'fulfilled_quantity' => 0,
            'shortage_quantity' => 0,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
