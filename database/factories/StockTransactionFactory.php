<?php

namespace Database\Factories;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockTransaction>
 */
class StockTransactionFactory extends Factory
{
    protected $model = StockTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $balanceBefore = $this->faker->numberBetween(100, 1000);

        return [
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => Warehouse::factory(),
            'item_id' => Item::factory(),
            'movement_type' => MovementType::Receipt,
            'signed_quantity' => $quantity,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $quantity,
            'source_type' => 'App\\Models\\Receipt',
            'source_id' => $this->faker->randomNumber(5),
            'reason' => $this->faker->sentence(),
            'performed_by' => User::factory(),
            'idempotency_key' => Str::random(32),
            'occurred_at' => now(),
            'reversal_of_id' => null,
            'metadata' => null,
        ];
    }

    public function receipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => MovementType::Receipt,
            'signed_quantity' => abs($attributes['signed_quantity'] ?? 10),
        ]);
    }

    public function issue(): static
    {
        return $this->state(function (array $attributes) {
            $qty = -abs($attributes['signed_quantity'] ?? 10);
            $before = $attributes['balance_before'] ?? 100;

            return [
                'movement_type' => MovementType::PickupIssue,
                'signed_quantity' => $qty,
                'balance_after' => $before + $qty,
            ];
        });
    }
}
