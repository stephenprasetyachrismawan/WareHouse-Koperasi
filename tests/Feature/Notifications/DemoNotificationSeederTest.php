<?php

namespace Tests\Feature\Notifications;

use App\Models\InboxNotification;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\DemoGoodsReceiptSeeder;
use Database\Seeders\DemoNotificationSeeder;
use Database\Seeders\DemoPickupSeeder;
use Database\Seeders\DemoProcurementSeeder;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoReturnDecisionSeeder;
use Database\Seeders\DemoReturnReplacementSeeder;
use Database\Seeders\DemoReturnSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StockFoundationSeeder;
use Database\Seeders\UserAndMembershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoNotificationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_notifications_and_is_idempotent(): void
    {
        $this->seed([
            RoleAndPermissionSeeder::class,
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
        ]);

        $this->seed(DemoNotificationSeeder::class);

        // The Return demo seeders run through real Actions, so their
        // notifications should already exist organically.
        $this->assertGreaterThan(0, InboxNotification::where('type', 'RETURN_SUBMITTED')->count());
        $this->assertGreaterThan(0, InboxNotification::where('type', 'RETURN_STATUS')->count());
        $this->assertGreaterThan(0, InboxNotification::where('type', 'REPLACEMENT_READY')->count());
        $this->assertGreaterThan(0, InboxNotification::where('type', 'PO_STATUS')->count());

        // This seeder fills the gap for the raw-created demo PR/Pickup rows.
        $this->assertGreaterThan(0, InboxNotification::where('type', 'APPROVAL_REQUIRED')->count());
        $this->assertGreaterThan(0, InboxNotification::where('type', 'PICKUP_REQUESTED')->count());
        $this->assertGreaterThan(0, InboxNotification::where('type', 'READY_FOR_PICKUP')->count());

        $countBefore = InboxNotification::count();

        $this->seed(DemoNotificationSeeder::class);

        $this->assertSame($countBefore, InboxNotification::count());
    }
}
