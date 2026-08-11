<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PickupRequestItem;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnRequestItemFactory extends Factory
{
    protected $model = ReturnRequestItem::class;

    public function definition(): array
    {
        return [
            'return_request_id' => ReturnRequest::factory(),
            'pickup_request_item_id' => PickupRequestItem::factory(),
            'item_id' => Item::factory(),
            'return_quantity' => $this->faker->numberBetween(1, 5),
            'barcode_verified' => false,
        ];
    }

    public function barcodeVerified(): self
    {
        return $this->state(fn (array $attributes) => [
            'barcode_verified' => true,
        ]);
    }
}
