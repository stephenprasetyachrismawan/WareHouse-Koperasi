<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-PUSAT')->first();
        if (! $warehouse) {
            return;
        }

        // 1. Master Suppliers
        $suppliersData = [
            [
                'name' => 'PT Indofood Sukses Makmur Tbk',
                'contact_name' => 'Budi Utomo',
                'email' => 'sales@indofood.co.id',
                'phone' => '081234567890',
                'address' => 'Sudirman Plaza, Indofood Tower, Jakarta',
                'is_active' => true,
                'notes' => 'Supplier utama mie instant dan minyak goreng',
            ],
            [
                'name' => 'PT Mayora Indah Tbk',
                'contact_name' => 'Ibu Siti Hawa',
                'email' => 'order@mayora.co.id',
                'phone' => '081298765432',
                'address' => 'Gedung Mayora, Tomang Raya, Jakarta',
                'is_active' => true,
                'notes' => 'Supplier biskuit Roma dan makanan ringan',
            ],
            [
                'name' => 'PT Wings Surya',
                'contact_name' => 'Hendra Kusuma',
                'email' => 'distribution@wings.co.id',
                'phone' => '081311223344',
                'address' => 'Jl. Kalisosok Kidul No. 2, Surabaya',
                'is_active' => true,
                'notes' => 'Supplier deterjen dan perlengkapan kebersihan',
            ],
            [
                'name' => 'PT Unilever Indonesia Tbk',
                'contact_name' => 'Dewi Sartika',
                'email' => 'supply@unilever.co.id',
                'phone' => '081555667788',
                'address' => 'Grha Unilever, BSD City, Tangerang',
                'is_active' => true,
                'notes' => 'Supplier sabun mandi dan personal care',
            ],
            [
                'name' => 'PT Garudafood Putra Putri Jaya Tbk',
                'contact_name' => 'Agus Priyono',
                'email' => 'sales@garudafood.co.id',
                'phone' => '081788990011',
                'address' => 'Wisma Garudafood, Jakarta Barat',
                'is_active' => true,
                'notes' => 'Supplier snack dan kacang',
            ],
        ];

        foreach ($suppliersData as $supData) {
            Supplier::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'name' => $supData['name']],
                array_merge(['uuid' => (string) Str::uuid()], $supData)
            );
        }

        // 2. Master Warehouse Locations
        $locationsData = [
            ['code' => 'A-01-01', 'name' => 'Rak A-01 (Sembako Utama)', 'is_active' => true],
            ['code' => 'A-02-01', 'name' => 'Rak A-02 (Minyak & Gula)', 'is_active' => true],
            ['code' => 'B-01-01', 'name' => 'Rak B-01 (Mie & Instant Food)', 'is_active' => true],
            ['code' => 'B-02-01', 'name' => 'Rak B-02 (Minuman & Kopi)', 'is_active' => true],
            ['code' => 'C-01-01', 'name' => 'Rak C-01 (Kebersihan & Sabun)', 'is_active' => true],
            ['code' => 'C-02-01', 'name' => 'Rak C-02 (Snack & Biskuit)', 'is_active' => true],
        ];

        foreach ($locationsData as $locData) {
            WarehouseLocation::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $locData['code']],
                array_merge(['uuid' => (string) Str::uuid()], $locData)
            );
        }

        // 3. Master Items + Barcodes
        $itemsData = [
            [
                'code' => 'BM-2L',
                'name' => 'Minyak Goreng Bimoli Pouch 2 Liter',
                'unit' => 'pouch',
                'minimum_stock' => 20,
                'description' => 'Minyak goreng kelapa sawit pouch 2 liter',
                'barcodes' => ['8991001000012', '8991001000013'],
            ],
            [
                'code' => 'BR-5KG',
                'name' => 'Beras Raja Lezat 5 kg',
                'unit' => 'karung',
                'minimum_stock' => 30,
                'description' => 'Beras pulen kemasan karung 5 kg',
                'barcodes' => ['8991001000029'],
            ],
            [
                'code' => 'GL-1KG',
                'name' => 'Gula Pasir Gulaku Premium 1 kg',
                'unit' => 'pack',
                'minimum_stock' => 25,
                'description' => 'Gula pasir tebu kristal putih 1 kg',
                'barcodes' => ['8991001000036'],
            ],
            [
                'code' => 'IM-GRG',
                'name' => 'Indomie Goreng Spesial (Dus 40 pcs)',
                'unit' => 'dus',
                'minimum_stock' => 50,
                'description' => 'Mie goreng instant karton isi 40 bungkus',
                'barcodes' => ['8991001000043'],
            ],
            [
                'code' => 'AQ-600',
                'name' => 'Air Mineral Aqua 600ml (Dus 24 botol)',
                'unit' => 'dus',
                'minimum_stock' => 40,
                'description' => 'Air mineral kemasan botol 600ml isi 24',
                'barcodes' => ['8991001000050'],
            ],
            [
                'code' => 'TP-BOX',
                'name' => 'Teh Celup Poci Box (25 sachet)',
                'unit' => 'box',
                'minimum_stock' => 15,
                'description' => 'Teh celup melati box isi 25 sachet',
                'barcodes' => ['8991001000067'],
            ],
            [
                'code' => 'KP-KPL',
                'name' => 'Kopi Kapal Api Spesial 165g',
                'unit' => 'pack',
                'minimum_stock' => 20,
                'description' => 'Kopi bubuk murni bungkus 165 gram',
                'barcodes' => ['8991001000074'],
            ],
            [
                'code' => 'RS-770',
                'name' => 'Deterjen Rinso Anti Noda 770g',
                'unit' => 'pouch',
                'minimum_stock' => 15,
                'description' => 'Deterjen bubuk pembersih noda 770 gram',
                'barcodes' => ['8991001000081'],
            ],
            [
                'code' => 'BR-450',
                'name' => 'Sabun Mandi Biore Body Wash 450ml',
                'unit' => 'pouch',
                'minimum_stock' => 10,
                'description' => 'Sabun mandi cair isi ulang 450ml',
                'barcodes' => ['8991001000098'],
            ],
            [
                'code' => 'RM-KLP',
                'name' => 'Biskuit Roma Kelapa 300g Kaleng',
                'unit' => 'kaleng',
                'minimum_stock' => 25,
                'description' => 'Biskuit rasa kelapa gurih kaleng 300 gram',
                'barcodes' => ['8991001000104'],
            ],
        ];

        foreach ($itemsData as $itemInfo) {
            $barcodes = $itemInfo['barcodes'];
            unset($itemInfo['barcodes']);

            $item = Item::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $itemInfo['code']],
                array_merge(['uuid' => (string) Str::uuid(), 'is_active' => true], $itemInfo)
            );

            foreach ($barcodes as $bc) {
                $item->barcodes()->firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'barcode' => $bc,
                ]);
            }
        }
    }
}
