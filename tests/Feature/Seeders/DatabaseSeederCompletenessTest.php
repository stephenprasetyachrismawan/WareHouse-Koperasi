<?php

namespace Tests\Feature\Seeders;

use App\Models\Approval;
use App\Models\GoodsReceipt;
use App\Models\InboxNotification;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\QualityInspection;
use App\Models\ReturnRequest;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_demo_seed_covers_each_core_business_workflow(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Warehouse::count());
        $this->assertGreaterThanOrEqual(10, User::count());
        $this->assertGreaterThan(0, Item::count());
        $this->assertGreaterThan(0, StockBalance::count());
        $this->assertGreaterThan(0, StockTransaction::count());
        $this->assertGreaterThan(0, PickupRequest::count());
        $this->assertGreaterThan(0, PurchaseRequest::count());
        $this->assertGreaterThan(0, PurchaseOrder::count());
        $this->assertGreaterThan(0, GoodsReceipt::count());
        $this->assertGreaterThan(0, QualityInspection::count());
        $this->assertGreaterThan(0, ReturnRequest::count());
        $this->assertGreaterThan(0, InboxNotification::count());
        $this->assertFalse(Approval::whereNull('uuid')->exists());

        $this->assertSame(
            ['WH-BARAT', 'WH-PUSAT'],
            Warehouse::query()->orderBy('code')->pluck('code')->all(),
        );

        $countsBeforeSecondSeed = [
            'warehouses' => Warehouse::count(),
            'items' => Item::count(),
            'stock_balances' => StockBalance::count(),
            'stock_transactions' => StockTransaction::count(),
            'pickups' => PickupRequest::count(),
            'purchase_requests' => PurchaseRequest::count(),
            'purchase_orders' => PurchaseOrder::count(),
            'goods_receipts' => GoodsReceipt::count(),
            'quality_inspections' => QualityInspection::count(),
            'returns' => ReturnRequest::count(),
            'notifications' => InboxNotification::count(),
        ];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($countsBeforeSecondSeed, [
            'warehouses' => Warehouse::count(),
            'items' => Item::count(),
            'stock_balances' => StockBalance::count(),
            'stock_transactions' => StockTransaction::count(),
            'pickups' => PickupRequest::count(),
            'purchase_requests' => PurchaseRequest::count(),
            'purchase_orders' => PurchaseOrder::count(),
            'goods_receipts' => GoodsReceipt::count(),
            'quality_inspections' => QualityInspection::count(),
            'returns' => ReturnRequest::count(),
            'notifications' => InboxNotification::count(),
        ]);
    }
}
