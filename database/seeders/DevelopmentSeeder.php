<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

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
            DemoJatengPickupSeeder::class,
            DemoJatengProcurementSeeder::class,
            DemoJatengPurchaseOrderSeeder::class,
            DemoJatengGoodsReceiptSeeder::class,
            DemoJatengReturnSeeder::class,
            DemoJatengReturnDecisionSeeder::class,
            DemoJatengReturnReplacementSeeder::class,
            // Runs last: backfills notifications for WH-PUSAT/WH-BARAT/WH-JATENG
            // alike, so it must run after every warehouse's business-transaction
            // data (including WH-JATENG's) already exists.
            DemoNotificationSeeder::class,
            DemoJatengNotificationSeeder::class,
        ]);

        // WarehouseMembership::hasPermission() sets the spatie/permission
        // "current team" as a side effect (see its setPermissionsTeamId()
        // call), and DemoNotificationSeeder's RecipientResolver lookups
        // trigger it once per warehouse. With three warehouses now split
        // across two different companies (WH-JATENG isn't in the same
        // company as WH-PUSAT/WH-BARAT), whichever warehouse's company was
        // checked last is left as the "current team" — reset it explicitly
        // to the main company (matching the pre-WH-JATENG behavior, where
        // every warehouse shared one company_id and this was never
        // observable) so nothing that runs after seeding — including tests
        // that call hasRole() without setting their own team context —
        // silently checks roles against the wrong company.
        $mainCompany = Warehouse::where('code', 'WH-PUSAT')->value('company_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($mainCompany);
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
