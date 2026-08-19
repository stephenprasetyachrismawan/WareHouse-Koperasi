<?php

namespace Database\Factories;

use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Models\GoodsReceiptItem;
use App\Models\QualityInspection;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityInspection>
 */
class QualityInspectionFactory extends Factory
{
    protected $model = QualityInspection::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'goods_receipt_item_id' => GoodsReceiptItem::factory(),
            'result' => QualityInspectionResult::Pass->value,
            'condition' => QualityInspectionCondition::Good->value,
            'notes' => $this->faker->sentence,
            'inspected_by' => User::factory(),
            'inspected_at' => now(),
        ];
    }

    public function passed(): static
    {
        return $this->state(fn () => [
            'result' => QualityInspectionResult::Pass->value,
            'condition' => QualityInspectionCondition::Good->value,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'result' => QualityInspectionResult::Fail->value,
            'condition' => QualityInspectionCondition::Damaged->value,
            'notes' => 'Barang rusak saat diperiksa.',
        ]);
    }
}
