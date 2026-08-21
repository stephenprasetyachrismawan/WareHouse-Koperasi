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
        $whPusat = Warehouse::where('code', 'WH-PUSAT')->first();
        $whBarat = Warehouse::where('code', 'WH-BARAT')->first();

        if ($whPusat) {
            $this->seedWarehouseStock($whPusat, 'staff.admin@koperasi.id', [
                'BM-2L' => 150,
                'BR-5KG' => 100,
                'GL-1KG' => 80,
                'TP-1KG' => 45,
                'GR-500' => 60,
                'IM-GRG' => 200,
                'IM-KUH' => 120,
                'SD-ABC' => 90,
                'AQ-600' => 10,  // Stok Kritis (< 40)
                'TP-BOX' => 5,   // Stok Kritis (< 15)
                'KP-KPL' => 45,
                'SKM-FF' => 70,
                'TB-SSR' => 30,
                'KC-ABC' => 55,
                'SS-ABC' => 40,
                'RS-770' => 60,
                'BR-450' => 35,
                'PG-190' => 28,
                'TS-250' => 50,
                'RM-KLP' => 75,
                'HVS-A4' => 22,
                'PLP-STD' => 18,
            ]);

            $this->seedLedgerDepth($whPusat, 'staff.admin@koperasi.id');
        }

        if ($whBarat) {
            $this->seedWarehouseStock($whBarat, 'staff.barat@koperasi.id', [
                'BM-2L' => 40,
                'BR-5KG' => 35,
                'GL-1KG' => 20,
                'TP-1KG' => 8,   // Stok Kritis (< 20)
                'GR-500' => 18,
                'IM-GRG' => 60,
                'IM-KUH' => 25,
                'SD-ABC' => 15,
                'AQ-600' => 50,
                'TP-BOX' => 12,
                'KP-KPL' => 10,
                'SKM-FF' => 22,
                'TB-SSR' => 8,
                'KC-ABC' => 12,
                'SS-ABC' => 10,
                // RS-770 intentionally omitted: zero-balance critical stock case.
                'BR-450' => 9,
                'PG-190' => 15,
                'TS-250' => 20,
                'RM-KLP' => 18,
                'HVS-A4' => 5,   // Stok Kritis (< 10)
                'PLP-STD' => 12,
            ]);
        }

        $whJateng = Warehouse::where('code', 'WH-JATENG')->first();
        if ($whJateng) {
            $this->seedWarehouseStock($whJateng, 'staff.jateng@koperasi.id', [
                'PUP-UREA' => 120,
                'PUP-NPK' => 90,
                'BNH-PADI' => 35,
                'BNH-JGG' => 8,    // Stok Kritis (< 15) — musim tanam jagung baru saja dimulai
                'BR-25KG' => 60,
                'GRM-KRS' => 25,
                'IKN-ASIN' => 12,
                'IKN-TERI' => 6,   // Stok Kritis (< 10)
                'BMB-ANYM' => 22,
                // PWR-ALAM intentionally omitted: zero-balance case, supplier belum kirim ulang.
                'BM-1L' => 55,
                'GL-1KG-J' => 40,
                'IM-GRG-J' => 65,
                'AQ-600-J' => 30,
            ]);

            $this->seedLedgerDepthJateng($whJateng);
        }
    }

    /**
     * @param  array<string, int>  $initialStocks
     */
    private function seedWarehouseStock(Warehouse $warehouse, string $performedByEmail, array $initialStocks): void
    {
        $staffUser = User::where('email', $performedByEmail)->first();
        if (! $staffUser) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get()->keyBy('code');
        if ($items->isEmpty()) {
            return;
        }

        $action = new RecordStockMovementAction;

        foreach ($initialStocks as $code => $qty) {
            $item = $items->get($code);
            if (! $item) {
                continue;
            }

            try {
                $action->execute(new StockMovementInput(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    movementType: MovementType::OpeningBalance,
                    quantity: $qty,
                    performedBy: $staffUser->id,
                    idempotencyKey: "seed-initial-{$warehouse->id}-{$item->id}",
                    reason: 'Stok Awal Inventaris Gudang'
                ));
            } catch (\Throwable $e) {
                // Already seeded; safe to ignore.
            }
        }
    }

    /**
     * A handful of post-opening-balance movements so the ledger reads like a
     * warehouse that has actually been operating, not just freshly stocked.
     */
    private function seedLedgerDepth(Warehouse $warehouse, string $performedByEmail): void
    {
        $staffUser = User::where('email', $performedByEmail)->first();
        if (! $staffUser) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get()->keyBy('code');
        $action = new RecordStockMovementAction;

        $adjustments = [
            ['code' => 'RM-KLP', 'type' => MovementType::ManualAdjustmentOut, 'qty' => 6, 'reason' => 'Kaleng penyok ditemukan saat stock opname bulanan, dikeluarkan dari stok jual'],
            ['code' => 'BR-5KG', 'type' => MovementType::ManualAdjustmentIn, 'qty' => 4, 'reason' => 'Koreksi selisih hasil stock opname (stok fisik lebih banyak dari sistem)'],
            ['code' => 'PG-190', 'type' => MovementType::ManualAdjustmentOut, 'qty' => 3, 'reason' => 'Kemasan bocor akibat kelembaban gudang'],
        ];

        foreach ($adjustments as $index => $adj) {
            $item = $items->get($adj['code']);
            if (! $item) {
                continue;
            }

            try {
                $action->execute(new StockMovementInput(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    movementType: $adj['type'],
                    quantity: $adj['qty'],
                    performedBy: $staffUser->id,
                    idempotencyKey: "seed-ledger-depth-{$warehouse->id}-{$index}",
                    reason: $adj['reason'],
                ));
            } catch (\Throwable $e) {
                // Already seeded; safe to ignore.
            }
        }
    }

    private function seedLedgerDepthJateng(Warehouse $warehouse): void
    {
        $staffUser = User::where('email', 'staff.jateng@koperasi.id')->first();
        if (! $staffUser) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get()->keyBy('code');
        $action = new RecordStockMovementAction;

        $adjustments = [
            ['code' => 'IKN-ASIN', 'type' => MovementType::ManualAdjustmentOut, 'qty' => 2, 'reason' => 'Ikan asin lembab akibat kemasan robek, dikeluarkan dari stok jual'],
            ['code' => 'BR-25KG', 'type' => MovementType::ManualAdjustmentIn, 'qty' => 5, 'reason' => 'Koreksi selisih hasil stock opname panen susulan dari petani anggota'],
        ];

        foreach ($adjustments as $index => $adj) {
            $item = $items->get($adj['code']);
            if (! $item) {
                continue;
            }

            try {
                $action->execute(new StockMovementInput(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    movementType: $adj['type'],
                    quantity: $adj['qty'],
                    performedBy: $staffUser->id,
                    idempotencyKey: "seed-ledger-depth-jateng-{$warehouse->id}-{$index}",
                    reason: $adj['reason'],
                ));
            } catch (\Throwable $e) {
                // Already seeded; safe to ignore.
            }
        }
    }
}
