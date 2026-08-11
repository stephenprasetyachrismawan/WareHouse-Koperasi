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
        foreach (Warehouse::whereIn('code', ['WH-PUSAT', 'WH-BARAT'])->get() as $warehouse) {
            $this->seedSuppliers($warehouse);
            $this->seedLocations($warehouse);
            $this->seedItems($warehouse);
        }
    }

    private function seedSuppliers(Warehouse $warehouse): void
    {
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
            [
                'name' => 'PT Sinar Sosro',
                'contact_name' => 'Retno Wulandari',
                'email' => 'order@sosro.com',
                'phone' => '081399887766',
                'address' => 'Jl. Sultan Agung Km 28, Bekasi',
                'is_active' => true,
                'notes' => 'Supplier teh siap minum dan sirup',
            ],
            [
                'name' => 'PT Kino Indonesia Tbk',
                'contact_name' => 'Fajar Ramadhan',
                'email' => 'distribusi@kino.co.id',
                'phone' => '081666778899',
                'address' => 'Kino Tower, Kebon Jeruk, Jakarta',
                'is_active' => true,
                'notes' => 'Supplier pasta gigi, sikat gigi, dan personal care',
            ],
            [
                'name' => 'PT ABC President Indonesia',
                'contact_name' => 'Novita Sari',
                'email' => 'sales@abcpresident.co.id',
                'phone' => '081422334455',
                'address' => 'Jl. Raya Bekasi Km 24, Cakung, Jakarta',
                'is_active' => true,
                'notes' => 'Supplier kecap, saus sambal, dan sarden kaleng',
            ],
            [
                'name' => 'CV Alat Tulis Makmur',
                'contact_name' => 'Suryadi',
                'email' => 'order@atkmakmur.co.id',
                'phone' => '081511223300',
                'address' => 'Jl. Pasar Baru No. 88, Jakarta Pusat',
                'is_active' => true,
                'notes' => 'Supplier alat tulis dan kebutuhan kantor',
            ],
            [
                'name' => 'CV Berkah Distributor Non-Aktif',
                'contact_name' => 'Warsito',
                'email' => 'warsito@berkahdist.co.id',
                'phone' => '081233445566',
                'address' => 'Jl. Gudang Lama No. 7, Jakarta Utara',
                'is_active' => false,
                'notes' => 'Kontrak berakhir, tidak lagi dipakai sejak 2025',
            ],
        ];

        foreach ($suppliersData as $supData) {
            Supplier::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'name' => $supData['name']],
                array_merge(['uuid' => (string) Str::uuid()], $supData)
            );
        }
    }

    private function seedLocations(Warehouse $warehouse): void
    {
        $locationsData = [
            ['code' => 'A-01-01', 'name' => 'Rak A-01 (Sembako Utama)', 'is_active' => true],
            ['code' => 'A-02-01', 'name' => 'Rak A-02 (Minyak & Gula)', 'is_active' => true],
            ['code' => 'A-03-01', 'name' => 'Rak A-03 (Tepung & Bumbu Dapur)', 'is_active' => true],
            ['code' => 'B-01-01', 'name' => 'Rak B-01 (Mie & Instant Food)', 'is_active' => true],
            ['code' => 'B-02-01', 'name' => 'Rak B-02 (Minuman & Kopi)', 'is_active' => true],
            ['code' => 'B-03-01', 'name' => 'Rak B-03 (Sarden & Makanan Kaleng)', 'is_active' => true],
            ['code' => 'C-01-01', 'name' => 'Rak C-01 (Kebersihan & Sabun)', 'is_active' => true],
            ['code' => 'C-02-01', 'name' => 'Rak C-02 (Snack & Biskuit)', 'is_active' => true],
            ['code' => 'D-01-01', 'name' => 'Rak D-01 (Alat Tulis Kantor)', 'is_active' => true],
            ['code' => 'E-01-DOCK', 'name' => 'Area Bongkar Muat (Dock E-01)', 'is_active' => true],
            ['code' => 'X-99-OLD', 'name' => 'Rak X-99 (Ditutup, Renovasi)', 'is_active' => false],
        ];

        foreach ($locationsData as $locData) {
            WarehouseLocation::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $locData['code']],
                array_merge(['uuid' => (string) Str::uuid()], $locData)
            );
        }
    }

    private function seedItems(Warehouse $warehouse): void
    {
        $itemsData = [
            // Sembako
            ['code' => 'BM-2L', 'name' => 'Minyak Goreng Bimoli Pouch 2 Liter', 'unit' => 'pouch', 'minimum_stock' => 20, 'description' => 'Minyak goreng kelapa sawit pouch 2 liter', 'barcodes' => ['8991001000012', '8991001000013']],
            ['code' => 'BR-5KG', 'name' => 'Beras Raja Lezat 5 kg', 'unit' => 'karung', 'minimum_stock' => 30, 'description' => 'Beras pulen kemasan karung 5 kg', 'barcodes' => ['8991001000029']],
            ['code' => 'GL-1KG', 'name' => 'Gula Pasir Gulaku Premium 1 kg', 'unit' => 'pack', 'minimum_stock' => 25, 'description' => 'Gula pasir tebu kristal putih 1 kg', 'barcodes' => ['8991001000036']],
            ['code' => 'TP-1KG', 'name' => 'Tepung Terigu Segitiga Biru 1 kg', 'unit' => 'pack', 'minimum_stock' => 20, 'description' => 'Tepung terigu protein sedang 1 kg', 'barcodes' => ['8991001000111']],
            ['code' => 'GR-500', 'name' => 'Garam Dapur Beryodium 500g', 'unit' => 'pack', 'minimum_stock' => 15, 'description' => 'Garam meja beryodium kemasan 500 gram', 'barcodes' => ['8991001000128']],

            // Mie & makanan instant
            ['code' => 'IM-GRG', 'name' => 'Indomie Goreng Spesial (Dus 40 pcs)', 'unit' => 'dus', 'minimum_stock' => 50, 'description' => 'Mie goreng instant karton isi 40 bungkus', 'barcodes' => ['8991001000043']],
            ['code' => 'IM-KUH', 'name' => 'Indomie Kuah Ayam Bawang (Dus 40 pcs)', 'unit' => 'dus', 'minimum_stock' => 40, 'description' => 'Mie kuah instant karton isi 40 bungkus', 'barcodes' => ['8991001000135']],
            ['code' => 'SD-ABC', 'name' => 'Sarden ABC Saus Tomat 155g', 'unit' => 'kaleng', 'minimum_stock' => 20, 'description' => 'Sarden kaleng saus tomat pedas 155 gram', 'barcodes' => ['8991001000142']],

            // Minuman
            ['code' => 'AQ-600', 'name' => 'Air Mineral Aqua 600ml (Dus 24 botol)', 'unit' => 'dus', 'minimum_stock' => 40, 'description' => 'Air mineral kemasan botol 600ml isi 24', 'barcodes' => ['8991001000050']],
            ['code' => 'TP-BOX', 'name' => 'Teh Celup Poci Box (25 sachet)', 'unit' => 'box', 'minimum_stock' => 15, 'description' => 'Teh celup melati box isi 25 sachet', 'barcodes' => ['8991001000067']],
            ['code' => 'KP-KPL', 'name' => 'Kopi Kapal Api Spesial 165g', 'unit' => 'pack', 'minimum_stock' => 20, 'description' => 'Kopi bubuk murni bungkus 165 gram', 'barcodes' => ['8991001000074']],
            ['code' => 'SKM-FF', 'name' => 'Susu Kental Manis Frisian Flag 370g', 'unit' => 'kaleng', 'minimum_stock' => 25, 'description' => 'Susu kental manis kaleng 370 gram', 'barcodes' => ['8991001000159']],
            ['code' => 'TB-SSR', 'name' => 'Teh Botol Sosro 450ml (Kotak 24 botol)', 'unit' => 'kotak', 'minimum_stock' => 15, 'description' => 'Teh siap minum botol kotak isi 24', 'barcodes' => ['8991001000166']],

            // Bumbu dapur
            ['code' => 'KC-ABC', 'name' => 'Kecap Manis ABC 600ml', 'unit' => 'botol', 'minimum_stock' => 20, 'description' => 'Kecap manis kemasan botol 600ml', 'barcodes' => ['8991001000173']],
            ['code' => 'SS-ABC', 'name' => 'Saus Sambal ABC 335ml', 'unit' => 'botol', 'minimum_stock' => 20, 'description' => 'Saus sambal pedas kemasan botol 335ml', 'barcodes' => ['8991001000180']],

            // Kebersihan
            ['code' => 'RS-770', 'name' => 'Deterjen Rinso Anti Noda 770g', 'unit' => 'pouch', 'minimum_stock' => 15, 'description' => 'Deterjen bubuk pembersih noda 770 gram', 'barcodes' => ['8991001000081']],
            ['code' => 'BR-450', 'name' => 'Sabun Mandi Biore Body Wash 450ml', 'unit' => 'pouch', 'minimum_stock' => 10, 'description' => 'Sabun mandi cair isi ulang 450ml', 'barcodes' => ['8991001000098']],
            ['code' => 'PG-190', 'name' => 'Pasta Gigi Ciptadent 190g', 'unit' => 'pcs', 'minimum_stock' => 20, 'description' => 'Pasta gigi keluarga tube 190 gram', 'barcodes' => ['8991001000197']],
            ['code' => 'TS-250', 'name' => 'Tisu Paseo 250 Lembar', 'unit' => 'pack', 'minimum_stock' => 25, 'description' => 'Tisu wajah isi 250 lembar per pack', 'barcodes' => ['8991001000203']],

            // Snack
            ['code' => 'RM-KLP', 'name' => 'Biskuit Roma Kelapa 300g Kaleng', 'unit' => 'kaleng', 'minimum_stock' => 25, 'description' => 'Biskuit rasa kelapa gurih kaleng 300 gram', 'barcodes' => ['8991001000104']],

            // Alat tulis kantor
            ['code' => 'HVS-A4', 'name' => 'Kertas HVS A4 80gsm (Rim 500 lembar)', 'unit' => 'rim', 'minimum_stock' => 10, 'description' => 'Kertas fotokopi A4 80gsm isi 500 lembar', 'barcodes' => ['8991001000210']],
            ['code' => 'PLP-STD', 'name' => 'Pulpen Standard AE7 (Box 12 pcs)', 'unit' => 'box', 'minimum_stock' => 10, 'description' => 'Pulpen tinta hitam box isi 12 batang', 'barcodes' => ['8991001000227']],
        ];

        foreach ($itemsData as $itemInfo) {
            $barcodes = $itemInfo['barcodes'];
            unset($itemInfo['barcodes']);

            $item = Item::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $itemInfo['code']],
                array_merge(['uuid' => (string) Str::uuid(), 'is_active' => true], $itemInfo)
            );

            foreach ($barcodes as $index => $bc) {
                $item->barcodes()->firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'barcode' => $bc,
                ], [
                    'is_primary' => $index === 0,
                ]);
            }
        }
    }
}
