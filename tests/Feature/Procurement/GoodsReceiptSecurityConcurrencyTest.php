<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\CompleteQualityInspectionAction;
use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Enums\WarehouseRole;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\QualityInspection;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class GoodsReceiptSecurityConcurrencyTest extends TestCase
{
    use RefreshDatabase;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, CompanyAndWarehouseSeeder::class]);
    }

    private function sentPurchaseOrder(Warehouse $warehouse, int $quantity = 10): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::SentToSupplier->value,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_id' => Item::factory()->create(['warehouse_id' => $warehouse->id])->id,
            'ordered_quantity' => $quantity,
        ]);

        return $purchaseOrder->fresh('items');
    }

    // -----------------------------------------------------------------
    // Tenant isolation / IDOR
    // -----------------------------------------------------------------

    public function test_cross_tenant_goods_receipt_view_is_denied(): void
    {
        $warehouse1 = Warehouse::first();
        $warehouse2 = Warehouse::factory()->create();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse1);

        $foreignReceipt = GoodsReceipt::factory()->create(['warehouse_id' => $warehouse2->id]);

        $response = $this->actingAs($user)->get(route('procurement.receipts.show', $foreignReceipt->uuid));

        $response->assertStatus(403);
    }

    public function test_cannot_forge_a_receipt_for_another_warehouses_purchase_order(): void
    {
        Gate::before(fn () => true);

        $warehouse1 = Warehouse::first();
        $warehouse2 = Warehouse::factory()->create();
        $user = User::factory()->create();

        $foreignPo = $this->sentPurchaseOrder($warehouse2);

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse1->id,
            purchaseOrderId: $foreignPo->id,
            receivedQuantities: $foreignPo->items->pluck('ordered_quantity', 'id')->all(),
        ));
    }

    public function test_evidence_download_is_denied_for_another_tenant(): void
    {
        $warehouse1 = Warehouse::first();
        $warehouse2 = Warehouse::factory()->create();
        $user = $this->createAuthorizedUser(WarehouseRole::StaffAdmin, $warehouse1);

        $foreignReceiptItem = GoodsReceiptItem::factory()->create(['warehouse_id' => $warehouse2->id]);
        $foreignInspection = QualityInspection::factory()->create([
            'warehouse_id' => $warehouse2->id,
            'goods_receipt_item_id' => $foreignReceiptItem->id,
            'evidence_path' => 'qc-evidence/fake.jpg',
            'evidence_mime' => 'image/jpeg',
        ]);

        $response = $this->actingAs($user)->get(route('procurement.qc.evidence', $foreignInspection->uuid));

        $response->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Double receipt / double QC ("concurrency")
    // -----------------------------------------------------------------

    public function test_only_one_of_two_receipt_attempts_for_the_same_po_succeeds(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $purchaseOrder = $this->sentPurchaseOrder($warehouse);
        $quantities = $purchaseOrder->items->pluck('ordered_quantity', 'id')->all();

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $quantities,
        ));

        $this->expectException(\Exception::class);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->fresh()->id,
            receivedQuantities: $quantities,
        ));

        $this->assertSame(1, GoodsReceipt::where('purchase_order_id', $purchaseOrder->id)->count());
    }

    public function test_only_one_of_two_qc_attempts_for_the_same_item_succeeds(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $purchasing = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $inspectorA = $this->createAuthorizedUser(WarehouseRole::StaffAdmin, $warehouse);

        $purchaseOrder = $this->sentPurchaseOrder($warehouse);
        $receipt = app(RecordGoodsReceiptAction::class)->execute($purchasing, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $purchaseOrder->items->pluck('ordered_quantity', 'id')->all(),
        ));
        $receiptItem = $receipt->items->first();

        $action = app(CompleteQualityInspectionAction::class);

        $action->execute($inspectorA, $receiptItem, new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
        ));

        $this->expectException(\Exception::class);

        $action->execute($inspectorA, $receiptItem->fresh(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Fail,
            condition: QualityInspectionCondition::Damaged,
            notes: 'Second inspector should not be able to override.',
        ));

        $this->assertSame(1, QualityInspection::where('goods_receipt_item_id', $receiptItem->id)->count());
        $this->assertSame(1, StockTransaction::where('warehouse_id', $warehouse->id)->count());
    }

    // -----------------------------------------------------------------
    // No stock mutation before PASS (explicit end-to-end regression)
    // -----------------------------------------------------------------

    public function test_stock_remains_unchanged_through_po_send_and_pending_receipt(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::Purchasing, $warehouse);
        $purchaseOrder = $this->sentPurchaseOrder($warehouse, 40);

        app(RecordGoodsReceiptAction::class)->execute($user, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $purchaseOrder->id,
            receivedQuantities: $purchaseOrder->items->pluck('ordered_quantity', 'id')->all(),
        ));

        $this->assertSame(0, StockTransaction::where('warehouse_id', $warehouse->id)->count());
        $this->assertSame(0, StockBalance::where('warehouse_id', $warehouse->id)->count());
    }
}
