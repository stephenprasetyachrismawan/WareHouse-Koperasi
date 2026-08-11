<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\PurchaseOrderStatus;
use App\Enums\WarehouseRole;
use App\Livewire\Procurement\GoodsReceiptQueue;
use App\Livewire\Procurement\GoodsReceiptShow;
use App\Livewire\Procurement\QcQueue;
use App\Livewire\Procurement\RecordGoodsReceipt;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class GoodsReceiptLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, CompanyAndWarehouseSeeder::class]);
    }

    private function createAuthorizedUser(WarehouseRole $role, Warehouse $warehouse): User
    {
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $user->assignRole($role->value);
        $user->warehouseMemberships()->create([
            'warehouse_id' => $warehouse->id,
            'company_id' => $warehouse->company_id,
            'role' => $role->value,
            'status' => 'active',
        ]);

        return $user;
    }

    private function sentPurchaseOrder(Warehouse $warehouse): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::SentToSupplier->value,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_id' => Item::factory()->create(['warehouse_id' => $warehouse->id])->id,
            'ordered_quantity' => 10,
        ]);

        return $purchaseOrder->fresh('items');
    }

    public function test_purchasing_can_see_receivable_purchase_orders_in_queue(): void
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $this->sentPurchaseOrder($warehouse);

        Livewire::actingAs($user)
            ->test(GoodsReceiptQueue::class)
            ->assertStatus(200)
            ->assertSee('Menunggu Diterima');
    }

    public function test_staff_admin_cannot_access_record_receipt_permission_denied(): void
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::StaffAdmin, $warehouse);
        $purchaseOrder = $this->sentPurchaseOrder($warehouse);

        $response = $this->actingAs($user)->get(route('procurement.receipts.create', $purchaseOrder->uuid));

        $response->assertStatus(403);
    }

    public function test_purchasing_can_record_receipt_through_the_form(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $purchaseOrder = $this->sentPurchaseOrder($warehouse);
        $poItem = $purchaseOrder->items->first();

        Livewire::actingAs($user)
            ->test(RecordGoodsReceipt::class, ['purchaseOrder' => $purchaseOrder])
            ->set("receivedQuantities.{$poItem->id}", 10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('goods_receipts', ['purchase_order_id' => $purchaseOrder->id]);
    }

    public function test_staff_admin_can_pass_qc_through_the_show_page(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $staffAdmin = $this->createAuthorizedUser(WarehouseRole::StaffAdmin, $warehouse);
        $purchasing = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $purchaseOrder = $this->sentPurchaseOrder($warehouse);

        $receipt = app(RecordGoodsReceiptAction::class)->execute($purchasing, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $purchaseOrder->items->pluck('ordered_quantity', 'id')->all(),
        ));

        $receiptItem = $receipt->items->first();

        Livewire::actingAs($staffAdmin)
            ->test(GoodsReceiptShow::class, ['goodsReceipt' => $receipt])
            ->call('openQcModal', $receiptItem->id)
            ->set('result', 'PASS')
            ->set('condition', 'GOOD')
            ->call('submitQc')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('quality_inspections', [
            'goods_receipt_item_id' => $receiptItem->id,
            'result' => 'PASS',
        ]);
        $this->assertSame(1, StockTransaction::where('warehouse_id', $warehouse->id)->count());
    }

    public function test_qc_queue_lists_pending_items(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $staffAdmin = $this->createAuthorizedUser(WarehouseRole::StaffAdmin, $warehouse);
        $purchasing = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $purchaseOrder = $this->sentPurchaseOrder($warehouse);

        app(RecordGoodsReceiptAction::class)->execute($purchasing, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $purchaseOrder->items->pluck('ordered_quantity', 'id')->all(),
        ));

        Livewire::actingAs($staffAdmin)
            ->test(QcQueue::class)
            ->assertStatus(200)
            ->assertSee($purchaseOrder->po_number);
    }

    public function test_cross_tenant_receipt_access_denied(): void
    {
        $warehouse1 = Warehouse::first();
        $warehouse2 = Warehouse::factory()->create();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse1);

        $foreignReceipt = GoodsReceipt::factory()->create(['warehouse_id' => $warehouse2->id]);

        $response = $this->actingAs($user)->get(route('procurement.receipts.show', $foreignReceipt->uuid));

        $response->assertStatus(403);
    }
}
