<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseRequestItemFactory extends Factory
{
    protected $model = PurchaseRequestItem::class;

    public function definition(): array
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'item_id' => Item::factory(),
            'requested_quantity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
