<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => Warehouse::factory(),
            'code' => 'ITM-'.$this->faker->unique()->randomNumber(5, true),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'unit' => $this->faker->randomElement(['pcs', 'box', 'kg', 'liter']),
            'minimum_stock' => $this->faker->numberBetween(10, 50),
            'is_active' => true,
            'archived_at' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            // Note: The actual critical state is based on stock balance < minimum_stock.
            // This just sets minimum stock high enough to make it easy to be critical.
            'minimum_stock' => 1000,
        ]);
    }

    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_stock' => 0,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }

    public function noHistory(): static
    {
        return $this->state(fn (array $attributes) => [
            // Setup for no history, although transactions are in another table.
        ]);
    }
}
