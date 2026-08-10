<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanyAndWarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'KSP-SU'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Koperasi Karyawan Sejahtera Utama',
                'status' => 'active',
            ]
        );

        Warehouse::firstOrCreate(
            ['code' => 'WH-PUSAT', 'company_id' => $company->id],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Gudang Utama Logistik',
                'address' => 'Jl. Industri Raya No. 45, Jakarta Barat',
                'status' => 'active',
            ]
        );

        Warehouse::firstOrCreate(
            ['code' => 'WH-BARAT', 'company_id' => $company->id],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Gudang Cabang Barat',
                'address' => 'Jl. Daan Mogot Km 12, Tangerang',
                'status' => 'active',
            ]
        );
    }
}
