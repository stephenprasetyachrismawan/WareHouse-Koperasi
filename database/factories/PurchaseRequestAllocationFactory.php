<?php

namespace Database\Factories;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequestAllocation;
use App\Models\PurchaseRequestGroup;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseRequestAllocationFactory extends Factory
{
    protected $model = PurchaseRequestAllocation::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'purchase_request_item_id' => PurchaseRequestItem::factory(),
            'purchase_request_group_id' => PurchaseRequestGroup::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'allocated_quantity' => $this->faker->numberBetween(1, 10),
            'allocated_by' => User::factory(),
        ];
    }
}
