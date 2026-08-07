<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemBarcode>
 */
class ItemBarcodeFactory extends Factory
{
    protected $model = ItemBarcode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'item_id' => Item::factory(),
            'barcode' => $this->faker->ean13(),
            'is_primary' => true,
        ];
    }
}
