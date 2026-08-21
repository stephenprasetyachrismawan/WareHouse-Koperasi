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
        $whPusat = Warehouse::where('code', 'WH-PUSAT')->first();
        $whBarat = Warehouse::where('code', 'WH-BARAT')->first();

        if (! $whPusat) {
            return;
        }

        $company = $whPusat->company;

        // Platform-level super admin (no warehouse membership; operates via impersonation).
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

        // Company-wide App Admin, based at the main warehouse.
        $appAdmin = $this->makeUser('admin.pusat@koperasi.id', 'Budi Santoso (App Admin)');
        $this->createMembership($appAdmin, $whPusat, WarehouseRole::AppAdmin);

        // --- WH-PUSAT (Gudang Utama Logistik, Jakarta) operational staff ---
        $kepalaPusat = $this->makeUser('kepala.gudang@koperasi.id', 'Hendra Setiawan (Kepala Gudang Pusat)');
        $this->createMembership($kepalaPusat, $whPusat, WarehouseRole::KepalaGudang);

        $staffPusat = $this->makeUser('staff.admin@koperasi.id', 'Siti Rahma (Staff Admin Gudang Pusat)');
        $this->createMembership($staffPusat, $whPusat, WarehouseRole::StaffAdmin);

        $purchasingPusat = $this->makeUser('purchasing@koperasi.id', 'Dewi Lestari (Purchasing Staff Pusat)');
        $this->createMembership($purchasingPusat, $whPusat, WarehouseRole::Purchasing);

        $koperasi1 = $this->makeUser('koperasi.unit1@koperasi.id', 'Koperasi Unit Produksi A');
        $this->createMembership($koperasi1, $whPusat, WarehouseRole::Koperasi);

        $koperasi2 = $this->makeUser('koperasi.unit2@koperasi.id', 'Koperasi Unit Jasa B');
        $this->createMembership($koperasi2, $whPusat, WarehouseRole::Koperasi);

        if (! $whBarat) {
            return;
        }

        // --- WH-BARAT (Gudang Cabang Barat, Tangerang) operational staff ---
        $kepalaBarat = $this->makeUser('kepala.barat@koperasi.id', 'Yusuf Maulana (Kepala Gudang Cabang Barat)');
        $this->createMembership($kepalaBarat, $whBarat, WarehouseRole::KepalaGudang);

        $staffBarat = $this->makeUser('staff.barat@koperasi.id', 'Rina Anggraini (Staff Admin Gudang Barat)');
        $this->createMembership($staffBarat, $whBarat, WarehouseRole::StaffAdmin);

        $purchasingBarat = $this->makeUser('purchasing.barat@koperasi.id', 'Agus Wibowo (Purchasing Staff Barat)');
        $this->createMembership($purchasingBarat, $whBarat, WarehouseRole::Purchasing);

        $koperasi3 = $this->makeUser('koperasi.unit3@koperasi.id', 'Koperasi Unit Simpan Pinjam C');
        $this->createMembership($koperasi3, $whBarat, WarehouseRole::Koperasi);

        $koperasi4 = $this->makeUser('koperasi.unit4@koperasi.id', 'Koperasi Unit Konsumsi D');
        $this->createMembership($koperasi4, $whBarat, WarehouseRole::Koperasi);

        $whJateng = Warehouse::where('code', 'WH-JATENG')->first();
        if (! $whJateng) {
            return;
        }

        // --- WH-JATENG (Gudang Koperasi Jateng, Semarang) — separate cooperative federation ---
        $adminJateng = $this->makeUser('admin.jateng@koperasi.id', 'Bambang Kusumo (App Admin Gudang Jateng)');
        $this->createMembership($adminJateng, $whJateng, WarehouseRole::AppAdmin);

        $kepalaJateng = $this->makeUser('kepala.jateng@koperasi.id', 'Sri Wahyuni (Kepala Gudang Jateng)');
        $this->createMembership($kepalaJateng, $whJateng, WarehouseRole::KepalaGudang);

        $staffJateng = $this->makeUser('staff.jateng@koperasi.id', 'Agus Purnomo (Staff Admin Gudang Jateng)');
        $this->createMembership($staffJateng, $whJateng, WarehouseRole::StaffAdmin);

        $purchasingJateng = $this->makeUser('purchasing.jateng@koperasi.id', 'Dwi Kartika (Purchasing Staff Jateng)');
        $this->createMembership($purchasingJateng, $whJateng, WarehouseRole::Purchasing);

        $koperasiTani = $this->makeUser('koperasi.tani@koperasi.id', 'Koperasi Tani Makmur Boyolali');
        $this->createMembership($koperasiTani, $whJateng, WarehouseRole::Koperasi);

        $koperasiWanita = $this->makeUser('koperasi.wanita@koperasi.id', 'Koperasi Wanita Sejahtera Salatiga');
        $this->createMembership($koperasiWanita, $whJateng, WarehouseRole::Koperasi);

        $koperasiNelayan = $this->makeUser('koperasi.nelayan@koperasi.id', 'Koperasi Nelayan Bahari Pekalongan');
        $this->createMembership($koperasiNelayan, $whJateng, WarehouseRole::Koperasi);

        // WH-JATENG belongs to a different Company than WH-PUSAT/WH-BARAT, so
        // (unlike before, when every warehouse shared one company_id) the
        // spatie/permission "current team" left dangling by createMembership()
        // above would otherwise point at the wrong company for anything that
        // checks a role right after this seeder runs without setting its own
        // team context first.
        setPermissionsTeamId($company->id);
    }

    private function makeUser(string $email, string $name): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
    }

    private function createMembership(User $user, Warehouse $warehouse, WarehouseRole $role): void
    {
        setPermissionsTeamId($warehouse->company_id);
        $user->assignRole($role->value);

        $user->warehouseMemberships()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'role' => $role->value,
            ],
            [
                'company_id' => $warehouse->company_id,
                'status' => 'active',
            ]
        );
    }
}
