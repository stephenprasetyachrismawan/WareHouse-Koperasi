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

        $whJateng = Warehouse::where('code', 'WH-JATENG')->first();
        if ($whJateng) {
            $this->seedSuppliersJateng($whJateng);
            $this->seedLocationsJateng($whJateng);
            $this->seedItemsJateng($whJateng);
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

    /**
     * WH-JATENG serves three very different member cooperatives (a farmer
     * group, a women's craft/food group, and a fishing group), so its
     * supplier list leans agricultural/fishery instead of the pure
     * urban-sembako list above.
     */
    private function seedSuppliersJateng(Warehouse $warehouse): void
    {
        $suppliersData = [
            [
                'name' => 'PT Pupuk Sriwidjaja Palembang (Distributor Jateng)',
                'contact_name' => 'Slamet Riyadi',
                'email' => 'distribusi.jateng@pusri.co.id',
                'phone' => '081611223344',
                'address' => 'Jl. Ki Mangunsarkoro No. 12, Semarang',
                'is_active' => true,
                'notes' => 'Distributor resmi pupuk bersubsidi untuk wilayah Jawa Tengah',
            ],
            [
                'name' => 'CV Benih Unggul Boyolali',
                'contact_name' => 'Marto Wijoyo',
                'email' => 'order@benihboyolali.co.id',
                'phone' => '081722334455',
                'address' => 'Jl. Pandanaran No. 5, Boyolali',
                'is_active' => true,
                'notes' => 'Supplier benih padi dan sayuran bersertifikat',
            ],
            [
                'name' => 'PT Garam Rebus Pekalongan',
                'contact_name' => 'Wahyu Setiadi',
                'email' => 'sales@garampekalongan.co.id',
                'phone' => '081833445566',
                'address' => 'Jl. Pantai Sari No. 21, Pekalongan',
                'is_active' => true,
                'notes' => 'Supplier garam krosok dan ikan asin untuk koperasi nelayan',
            ],
            [
                'name' => 'PT Indofood Sukses Makmur Tbk (Cabang Semarang)',
                'contact_name' => 'Ratna Kumala',
                'email' => 'semarang@indofood.co.id',
                'phone' => '081944556677',
                'address' => 'Jl. Kaligawe Raya Km 4, Semarang',
                'is_active' => true,
                'notes' => 'Supplier sembako umum (mie instant, minyak goreng) untuk regional Jateng',
            ],
            [
                'name' => 'CV Anyaman Mandiri Salatiga',
                'contact_name' => 'Yuli Astuti',
                'email' => 'produksi@anyamanmandiri.co.id',
                'phone' => '081255667788',
                'address' => 'Jl. Diponegoro No. 45, Salatiga',
                'is_active' => true,
                'notes' => 'Supplier bahan baku kerajinan anyaman untuk Koperasi Wanita Sejahtera',
            ],
            [
                'name' => 'UD Alat Tani Sejahtera (Non-Aktif)',
                'contact_name' => 'Karyo Utomo',
                'email' => 'karyo@alattani.co.id',
                'phone' => '081366778899',
                'address' => 'Jl. Raya Solo-Boyolali Km 9',
                'is_active' => false,
                'notes' => 'Sudah tutup usaha sejak awal 2026, kontrak tidak diperpanjang',
            ],
        ];

        foreach ($suppliersData as $supData) {
            Supplier::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'name' => $supData['name']],
                array_merge(['uuid' => (string) Str::uuid()], $supData)
            );
        }
    }

    private function seedLocationsJateng(Warehouse $warehouse): void
    {
        $locationsData = [
            ['code' => 'J-A-01', 'name' => 'Rak J-A-01 (Pupuk & Benih)', 'is_active' => true],
            ['code' => 'J-A-02', 'name' => 'Rak J-A-02 (Hasil Tani Kering)', 'is_active' => true],
            ['code' => 'J-B-01', 'name' => 'Rak J-B-01 (Garam & Ikan Asin)', 'is_active' => true],
            ['code' => 'J-B-02', 'name' => 'Gudang Dingin J-B-02 (Produk Perikanan)', 'is_active' => true],
            ['code' => 'J-C-01', 'name' => 'Rak J-C-01 (Sembako Umum)', 'is_active' => true],
            ['code' => 'J-C-02', 'name' => 'Rak J-C-02 (Bahan Kerajinan)', 'is_active' => true],
            ['code' => 'J-D-DOCK', 'name' => 'Area Bongkar Muat Gudang Jateng', 'is_active' => true],
            ['code' => 'J-X-OLD', 'name' => 'Rak J-X (Ditutup, Renovasi Atap)', 'is_active' => false],
        ];

        foreach ($locationsData as $locData) {
            WarehouseLocation::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $locData['code']],
                array_merge(['uuid' => (string) Str::uuid()], $locData)
            );
        }
    }

    private function seedItemsJateng(Warehouse $warehouse): void
    {
        $itemsData = [
            // Pertanian — untuk Koperasi Tani Makmur Boyolali
            ['code' => 'PUP-UREA', 'name' => 'Pupuk Urea Bersubsidi 50kg', 'unit' => 'karung', 'minimum_stock' => 40, 'description' => 'Pupuk urea granul bersubsidi pemerintah kemasan 50 kg', 'barcodes' => ['8992001000010']],
            ['code' => 'PUP-NPK', 'name' => 'Pupuk NPK Phonska 50kg', 'unit' => 'karung', 'minimum_stock' => 30, 'description' => 'Pupuk majemuk NPK kemasan 50 kg', 'barcodes' => ['8992001000027']],
            ['code' => 'BNH-PADI', 'name' => 'Benih Padi Ciherang Bersertifikat 5kg', 'unit' => 'sak', 'minimum_stock' => 20, 'description' => 'Benih padi unggul bersertifikat label biru', 'barcodes' => ['8992001000034']],
            ['code' => 'BNH-JGG', 'name' => 'Benih Jagung Hibrida 1kg', 'unit' => 'pack', 'minimum_stock' => 15, 'description' => 'Benih jagung hibrida daya tumbuh tinggi', 'barcodes' => ['8992001000041']],
            ['code' => 'BR-25KG', 'name' => 'Beras Hasil Panen Petani Boyolali 25kg', 'unit' => 'karung', 'minimum_stock' => 25, 'description' => 'Beras hasil panen anggota koperasi tani, karung 25 kg', 'barcodes' => ['8992001000058']],

            // Perikanan — untuk Koperasi Nelayan Bahari Pekalongan
            ['code' => 'GRM-KRS', 'name' => 'Garam Krosok 50kg', 'unit' => 'karung', 'minimum_stock' => 20, 'description' => 'Garam krosok untuk pengawetan ikan, karung 50 kg', 'barcodes' => ['8992001000065']],
            ['code' => 'IKN-ASIN', 'name' => 'Ikan Asin Jambal Roti 10kg', 'unit' => 'peti', 'minimum_stock' => 10, 'description' => 'Ikan asin jambal roti kemasan peti 10 kg', 'barcodes' => ['8992001000072']],
            ['code' => 'IKN-TERI', 'name' => 'Ikan Teri Nasi Kering 5kg', 'unit' => 'peti', 'minimum_stock' => 10, 'description' => 'Ikan teri nasi kering kemasan peti 5 kg', 'barcodes' => ['8992001000089']],

            // Kerajinan — untuk Koperasi Wanita Sejahtera Salatiga
            ['code' => 'BMB-ANYM', 'name' => 'Bambu Anyaman Siap Pakai (Ikat 20 lembar)', 'unit' => 'ikat', 'minimum_stock' => 15, 'description' => 'Bahan baku bambu untuk anyaman kerajinan, ikat isi 20 lembar', 'barcodes' => ['8992001000096']],
            ['code' => 'PWR-ALAM', 'name' => 'Pewarna Alami Kerajinan 500g', 'unit' => 'pack', 'minimum_stock' => 10, 'description' => 'Pewarna alami untuk finishing produk anyaman', 'barcodes' => ['8992001000102']],

            // Sembako umum (tetap ada, karena gudang ini juga melayani konsumsi harian anggota)
            ['code' => 'BM-1L', 'name' => 'Minyak Goreng Bimoli Pouch 1 Liter', 'unit' => 'pouch', 'minimum_stock' => 30, 'description' => 'Minyak goreng kelapa sawit pouch 1 liter', 'barcodes' => ['8992001000119']],
            ['code' => 'GL-1KG-J', 'name' => 'Gula Pasir Gulaku Premium 1 kg', 'unit' => 'pack', 'minimum_stock' => 25, 'description' => 'Gula pasir tebu kristal putih 1 kg', 'barcodes' => ['8992001000126']],
            ['code' => 'IM-GRG-J', 'name' => 'Indomie Goreng Spesial (Dus 40 pcs)', 'unit' => 'dus', 'minimum_stock' => 30, 'description' => 'Mie goreng instant karton isi 40 bungkus', 'barcodes' => ['8992001000133']],
            ['code' => 'AQ-600-J', 'name' => 'Air Mineral 600ml (Dus 24 botol)', 'unit' => 'dus', 'minimum_stock' => 20, 'description' => 'Air mineral kemasan botol 600ml isi 24', 'barcodes' => ['8992001000140']],
        ];

        foreach ($itemsData as $itemInfo) {
            $barcodes = $itemInfo['barcodes'];
            unset($itemInfo['barcodes']);

            $item = Item::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $itemInfo['code']],
                array_merge(['uuid' => (string) Str::uuid(), 'is_active' => true], $itemInfo)
            );

            // Every WH-JATENG item has exactly one barcode (unlike some
            // WH-PUSAT/WH-BARAT items above), so it's always the primary one.
            foreach ($barcodes as $bc) {
                $item->barcodes()->firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'barcode' => $bc,
                ], [
                    'is_primary' => true,
                ]);
            }
        }
    }
}
