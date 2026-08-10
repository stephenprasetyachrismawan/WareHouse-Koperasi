<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed demo users shown in the local testing account list.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $company = Company::firstOrCreate(
            ['code' => 'KOPERASI-PUSAT'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Koperasi Pusat',
                'status' => 'active',
            ],
        );

        $warehouse = Warehouse::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'WH-UTAMA'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Gudang Utama',
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
            ],
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@koperasi.id'],
            [
                'name' => 'Super Administrator Platform',
                'password' => $password,
                'status' => 'active',
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        setPermissionsTeamId($company->id);
        $superAdmin->assignRole('super_admin');

        $users = [
            ['name' => 'Budi Santoso', 'email' => 'admin.pusat@koperasi.id', 'role' => 'app_admin'],
            ['name' => 'Hendra Setiawan', 'email' => 'kepala.gudang@koperasi.id', 'role' => 'kepala_gudang'],
            ['name' => 'Siti Rahma', 'email' => 'staff.admin@koperasi.id', 'role' => 'staff_admin'],
            ['name' => 'Dewi Lestari', 'email' => 'purchasing@koperasi.id', 'role' => 'purchasing'],
            ['name' => 'Koperasi Unit Produksi A', 'email' => 'koperasi.unit1@koperasi.id', 'role' => 'koperasi'],
            ['name' => 'Koperasi Unit Jasa B', 'email' => 'koperasi.unit2@koperasi.id', 'role' => 'koperasi'],
        ];

        foreach ($users as $demoUser) {
            $user = User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => $password,
                    'status' => 'active',
                    'is_super_admin' => false,
                    'email_verified_at' => now(),
                ],
            );

            WarehouseMembership::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $user->id,
                    'role' => $demoUser['role'],
                ],
                [
                    'company_id' => $company->id,
                    'status' => 'active',
                ],
            );

            setPermissionsTeamId($company->id);
            $user->assignRole($demoUser['role']);
        }
    }
}
