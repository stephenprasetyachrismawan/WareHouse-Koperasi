<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'supplier_id' => Supplier::factory(),
            'po_number' => 'PO-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => PurchaseOrderStatus::Draft->value,
            'created_by' => User::factory(),
            'notes' => $this->faker->sentence,
        ];
    }
}
