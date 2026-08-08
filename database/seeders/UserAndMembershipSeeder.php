<?php

namespace Database\Seeders;

use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class UserAndMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-PUSAT')->first();
        if (! $warehouse) {
            return;
        }

        $company = $warehouse->company;

        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@koperasi.id'],
            [
                'name' => 'Super Administrator Platform',
                'password' => bcrypt('password'),
                'status' => 'active',
                'is_super_admin' => true,
            ]
        );
        setPermissionsTeamId($company->id);
        $superAdmin->assignRole('super_admin');

        // 2. App Admin
        $appAdmin = User::firstOrCreate(
            ['email' => 'admin.pusat@koperasi.id'],
            [
                'name' => 'Budi Santoso (App Admin)',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $this->createMembership($appAdmin, $warehouse, WarehouseRole::AppAdmin);

        // 3. Kepala Gudang
        $kepalaGudang = User::firstOrCreate(
            ['email' => 'kepala.gudang@koperasi.id'],
            [
                'name' => 'Hendra Setiawan (Kepala Gudang)',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $this->createMembership($kepalaGudang, $warehouse, WarehouseRole::KepalaGudang);

        // 4. Staff Admin
        $staffAdmin = User::firstOrCreate(
            ['email' => 'staff.admin@koperasi.id'],
            [
                'name' => 'Siti Rahma (Staff Admin Gudang)',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $this->createMembership($staffAdmin, $warehouse, WarehouseRole::StaffAdmin);

        // 5. Purchasing
        $purchasing = User::firstOrCreate(
            ['email' => 'purchasing@koperasi.id'],
            [
                'name' => 'Dewi Lestari (Purchasing Staff)',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $this->createMembership($purchasing, $warehouse, WarehouseRole::Purchasing);

        // 6. Koperasi Requester 1
        $koperasi1 = User::firstOrCreate(
            ['email' => 'koperasi.unit1@koperasi.id'],
            [
                'name' => 'Koperasi Unit Produksi A',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $this->createMembership($koperasi1, $warehouse, WarehouseRole::Koperasi);

        // 7. Koperasi Requester 2
        $koperasi2 = User::firstOrCreate(
            ['email' => 'koperasi.unit2@koperasi.id'],
            [
                'name' => 'Koperasi Unit Jasa B',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $this->createMembership($koperasi2, $warehouse, WarehouseRole::Koperasi);
    }

    private function createMembership(User $user, Warehouse $warehouse, WarehouseRole $role): void
    {
        setPermissionsTeamId($warehouse->company_id);
        $user->assignRole($role->value);

        $user->warehouseMemberships()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
            ],
            [
                'company_id' => $warehouse->company_id,
                'role' => $role->value,
                'status' => 'active',
            ]
        );
    }
}
