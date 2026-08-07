<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Enums\MovementType;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::first();
        if (! $warehouse) {
            return;
        }

        $user = User::whereHas('warehouseMemberships', fn ($q) => $q->where('warehouse_id', $warehouse->id))->first()
            ?? User::first();

        if (! $user) {
            return;
        }

        // Seed Warehouse Locations
        $locations = [
            ['code' => 'RAK-A1', 'name' => 'Rak A1 - Sembako & Minyak', 'description' => 'Zona Depan Kiri'],
            ['code' => 'RAK-A2', 'name' => 'Rak A2 - Beras & Tepung', 'description' => 'Zona Depan Kanan'],
            ['code' => 'RAK-B1', 'name' => 'Rak B1 - Gula & Bumbu', 'description' => 'Zona Tengah'],
            ['code' => 'RAK-C1', 'name' => 'Rak C1 - Minuman & Kemasan', 'description' => 'Zona Belakang'],
        ];

        foreach ($locations as $locData) {
            WarehouseLocation::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $locData['code']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $locData['name'],
                    'description' => $locData['description'],
                    'is_active' => true,
                ]
            );
        }

        // Seed Suppliers
        $suppliers = [
            ['name' => 'PT Indofood Sukses Makmur', 'contact_name' => 'Budi Santoso', 'email' => 'sales@indofood.co.id', 'phone' => '021-5551234'],
            ['name' => 'CV Sumber Sembako Jaya', 'contact_name' => 'Dewi Lestari', 'email' => 'dewi@sumbersembako.com', 'phone' => '081298765432'],
            ['name' => 'PT Wilmar Nabati Indonesia', 'contact_name' => 'Hendra Setiawan', 'email' => 'info@wilmar.co.id', 'phone' => '021-7778889'],
        ];

        foreach ($suppliers as $supData) {
            Supplier::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'name' => $supData['name']],
                [
                    'uuid' => (string) Str::uuid(),
                    'contact_name' => $supData['contact_name'],
                    'email' => $supData['email'],
                    'phone' => $supData['phone'],
                    'address' => 'Jl. Industri Sembako No. '.$warehouse->id,
                    'is_active' => true,
                ]
            );
        }

        // Seed Catalog Items & Initial Stock
        $itemsData = [
            ['code' => 'SKU-BRS-01', 'name' => 'Beras Pandan Wangi 5KG', 'unit' => 'BAG', 'min' => 20, 'qty' => 50, 'barcode' => '8991001000011'],
            ['code' => 'SKU-MYK-01', 'name' => 'Minyak Goreng Bimoli 2L', 'unit' => 'PCH', 'min' => 30, 'qty' => 15, 'barcode' => '8991001000028'], // Critical
            ['code' => 'SKU-GLA-01', 'name' => 'Gula Pasir Kristal 1KG', 'unit' => 'PCS', 'min' => 50, 'qty' => 120, 'barcode' => '8991001000035'],
            ['code' => 'SKU-TPG-01', 'name' => 'Tepung Terigu Segitiga Biru 1KG', 'unit' => 'PCS', 'min' => 25, 'qty' => 8, 'barcode' => '8991001000042'], // Critical
            ['code' => 'SKU-SUS-01', 'name' => 'Susu Kental Manis Frisian Flag 370g', 'unit' => 'CAN', 'min' => 40, 'qty' => 100, 'barcode' => '8991001000059'],
            ['code' => 'SKU-KOP-01', 'name' => 'Kopi Kapal Api Special 165g', 'unit' => 'PACK', 'min' => 15, 'qty' => 0, 'barcode' => '8991001000066'], // Critical (0)
        ];

        $action = new RecordStockMovementAction;

        foreach ($itemsData as $itmData) {
            $item = Item::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $itmData['code']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $itmData['name'],
                    'unit' => $itmData['unit'],
                    'minimum_stock' => $itmData['min'],
                    'is_active' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            ItemBarcode::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'barcode' => $itmData['barcode']],
                ['item_id' => $item->id, 'is_primary' => true]
            );

            // Record Opening Balance movement if stock balance not created yet
            if (! $item->stockBalance && $itmData['qty'] > 0) {
                $action->execute(new StockMovementInput(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    movementType: MovementType::OpeningBalance,
                    quantity: $itmData['qty'],
                    performedBy: $user->id,
                    idempotencyKey: 'seeder-opening-'.$item->id,
                    reason: 'Saldo awal seeder demo gudang',
                ));
            }
        }
    }
}
