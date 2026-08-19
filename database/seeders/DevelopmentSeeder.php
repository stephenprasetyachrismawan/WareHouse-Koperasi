<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates the demo/development dataset: companies, warehouses, seeded
 * users with fixed development passwords, master data, stock, and every
 * business-transaction demo seeder.
 *
 * This never runs the core IAM bootstrap (`RoleAndPermissionSeeder`) itself —
 * that is deployment-level state bootstrapped by a migration, safe for every
 * environment. This seeder is purely demo data and is guarded so fixed-
 * password demo accounts cannot be seeded into a production deployment by
 * accident (BATASAN.md §14, SECURITY-RULES.md §4.3).
 */
class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->demoSeedingAllowed()) {
            $this->command->warn(
                'Skipping demo/development seed data: APP_ENV is not local/testing and '
                .'ALLOW_DEMO_SEEDING is not enabled. Set ALLOW_DEMO_SEEDING=true to opt in explicitly.'
            );

            return;
        }

        $this->call([
            CompanyAndWarehouseSeeder::class,
            UserAndMembershipSeeder::class,
            MasterDataSeeder::class,
            StockFoundationSeeder::class,
            DemoPickupSeeder::class,
            DemoProcurementSeeder::class,
            DemoPurchaseOrderSeeder::class,
            DemoGoodsReceiptSeeder::class,
            DemoReturnSeeder::class,
            DemoReturnDecisionSeeder::class,
            DemoReturnReplacementSeeder::class,
            DemoNotificationSeeder::class,
        ]);
    }

    /**
     * Demo seeding is allowed in local/testing environments implicitly, or
     * anywhere else only via an explicit, non-default opt-in — never inferred
     * from hostname or APP_URL (BATASAN.md §27).
     */
    private function demoSeedingAllowed(): bool
    {
        return app()->environment(['local', 'testing']) || (bool) config('app.allow_demo_seeding');
    }
}
