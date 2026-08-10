<?php

namespace Database\Seeders;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Enums\MovementType;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class StockFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-PUSAT')->first();
        if (! $warehouse) {
            return;
        }

        $staffUser = User::where('email', 'staff.admin@koperasi.id')->first();
        if (! $staffUser) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get();
        if ($items->isEmpty()) {
            return;
        }

        $action = new RecordStockMovementAction;

        // Initial stock allocation per item
        $initialStocks = [
            'BM-2L' => 150,
            'BR-5KG' => 100,
            'GL-1KG' => 80,
            'IM-GRG' => 200,
            'AQ-600' => 10, // Stok Kritis (< 40)
            'TP-BOX' => 5,  // Stok Kritis (< 15)
            'KP-KPL' => 45,
            'RS-770' => 60,
            'BR-450' => 35,
            'RM-KLP' => 75,
        ];

        foreach ($items as $item) {
            if (! isset($initialStocks[$item->code])) {
                continue;
            }

            $qty = $initialStocks[$item->code];

            $input = new StockMovementInput(
                warehouseId: $warehouse->id,
                itemId: $item->id,
                movementType: MovementType::OpeningBalance,
                quantity: $qty,
                performedBy: $staffUser->id,
                idempotencyKey: "seed-initial-{$warehouse->id}-{$item->id}",
                reason: 'Stok Awal Inventaris Gudang'
            );

            try {
                $action->execute($input);
            } catch (\Throwable $e) {
                // Ignore if already seeded
            }
        }
    }
}
