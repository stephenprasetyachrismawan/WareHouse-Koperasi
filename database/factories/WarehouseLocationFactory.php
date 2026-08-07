<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WarehouseLocation>
 */
class WarehouseLocationFactory extends Factory
{
    protected $model = WarehouseLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = $this->faker->randomElement(['RACK', 'SHELF', 'ZONE', 'BIN']);
        $suffix = $this->faker->bothify('?-##');

        return [
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => Warehouse::factory(),
            'code' => strtoupper("{$prefix}-{$suffix}"),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
