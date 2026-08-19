<?php

namespace Database\Factories;

use App\Models\PurchaseRequestGroup;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequestGroup>
 */
class PurchaseRequestGroupFactory extends Factory
{
    protected $model = PurchaseRequestGroup::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'group_number' => 'PRG-'.$this->faker->unique()->numberBetween(1000, 9999),
            'created_by' => User::factory(),
            'notes' => $this->faker->sentence,
        ];
    }
}
