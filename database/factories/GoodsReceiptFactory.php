<?php

namespace Database\Factories;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'receipt_number' => 'GR-'.$this->faker->unique()->numberBetween(1000, 9999),
            'received_by' => User::factory(),
            'received_at' => now(),
            'status' => GoodsReceiptStatus::PendingQc->value,
            'notes' => $this->faker->sentence,
        ];
    }

    public function qcCompleted(): static
    {
        return $this->state(fn () => ['status' => GoodsReceiptStatus::QcCompleted->value]);
    }
}
