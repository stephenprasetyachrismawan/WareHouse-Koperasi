<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ReturnDisposal;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnDisposalFactory extends Factory
{
    protected $model = ReturnDisposal::class;

    public function definition(): array
    {
        return [
            'return_request_id' => ReturnRequest::factory(),
            'return_request_item_id' => ReturnRequestItem::factory(),
            'warehouse_id' => Warehouse::factory(),
            'item_id' => Item::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'disposed_by' => User::factory(),
            'disposed_at' => now(),
        ];
    }
}
