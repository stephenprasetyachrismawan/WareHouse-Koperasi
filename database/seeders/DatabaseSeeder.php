<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CompanyAndWarehouseSeeder::class,
            UserAndMembershipSeeder::class,
            MasterDataSeeder::class,
            StockFoundationSeeder::class,
            DemoUserSeeder::class,
            DemoPickupSeeder::class,
            DemoProcurementSeeder::class,
            DemoPurchaseOrderSeeder::class,
        ]);
    }
}
