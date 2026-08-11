<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\ApproveReturnAction;
use App\Actions\Returns\DetermineReturnFaultAction;
use App\Enums\ReturnFaultAttribution;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Models\Approval;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\QualityInspection;
use App\Models\ReturnDisposal;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveReturnActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $kepalaGudang;

    private Item $item;

    private ReturnRequest $returnRequest;

    private ReturnRequestItem $returnRequestItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->kepalaGudang = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        StockBalance::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 42,
        ]);

        $this->returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $this->warehouse->id]);
        $this->returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'item_id' => $this->item->id,
            'return_quantity' => 4,
        ]);
    }

    private function givePassedQc(): void
    {
        $goodsReceiptItem = GoodsReceiptItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
        ]);
        QualityInspection::factory()->passed()->create([
            'warehouse_id' => $this->warehouse->id,
            'goods_receipt_item_id' => $goodsReceiptItem->id,
        ]);
    }

    public function test_kepala_gudang_approves_with_qc_evidence_resulting_in_warehouse_attribution(): void
    {
        $this->givePassedQc();

        $result = app(ApproveReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest);

        $this->assertEquals(ReturnStatus::ReplacementPending, $result->status);
        $this->assertEquals($this->kepalaGudang->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);
        $this->assertEquals(ReturnFaultAttribution::Warehouse, $result->fault_attribution);
        $this->assertEquals(DetermineReturnFaultAction::RULE_VERSION, $result->fault_rule_version);

        $this->assertEquals(1, Approval::where('approvable_type', ReturnRequest::class)
            ->where('approvable_id', $this->returnRequest->id)
            ->where('status', 'APPROVED')
            ->count());

        $disposal = ReturnDisposal::where('return_request_item_id', $this->returnRequestItem->id)->first();
        $this->assertNotNull($disposal);
        $this->assertEquals(4, $disposal->quantity);
        $this->assertEquals($this->item->id, $disposal->item_id);
    }

    public function test_kepala_gudang_approves_without_qc_evidence_resulting_in_supplier_attribution(): void
    {
        $result = app(ApproveReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest);

        $this->assertEquals(ReturnFaultAttribution::Supplier, $result->fault_attribution);
    }

    public function test_approval_does_not_mutate_stock_balance_or_create_stock_transaction(): void
    {
        app(ApproveReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest);

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('item_id', $this->item->id)->first();
        $this->assertEquals(42, $balance->quantity);
        $this->assertSame(0, StockTransaction::count());
    }

    public function test_it_fails_if_return_is_not_waiting_approval(): void
    {
        $this->returnRequest->update(['status' => ReturnStatus::AdminVerified]);

        $this->expectException(\RuntimeException::class);
        app(ApproveReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest->fresh());
    }

    public function test_double_approve_retry_produces_a_single_approval_and_a_single_disposal(): void
    {
        app(ApproveReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest);

        try {
            app(ApproveReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest->fresh());
            $this->fail('Expected a RuntimeException on the second approval attempt.');
        } catch (\RuntimeException) {
            // expected: status is no longer WAITING_APPROVAL
        }

        $this->assertSame(1, Approval::where('approvable_type', ReturnRequest::class)->count());
        $this->assertSame(1, ReturnDisposal::count());

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('item_id', $this->item->id)->first();
        $this->assertEquals(42, $balance->quantity);
    }

    public function test_staff_admin_cannot_approve(): void
    {
        $staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staff->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(ApproveReturnAction::class)->execute($staff, $this->returnRequest);
    }

    public function test_purchasing_cannot_approve(): void
    {
        $purchasing = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $purchasing->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Purchasing->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(ApproveReturnAction::class)->execute($purchasing, $this->returnRequest);
    }

    public function test_koperasi_cannot_approve(): void
    {
        $koperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(ApproveReturnAction::class)->execute($koperasi, $this->returnRequest);
    }

    public function test_cross_tenant_kepala_gudang_cannot_approve(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        $otherHead = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherHead->id,
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(ApproveReturnAction::class)->execute($otherHead, $this->returnRequest);
    }

    public function test_inactive_membership_cannot_approve(): void
    {
        $inactiveHead = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $inactiveHead->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'inactive',
        ]);

        $this->expectException(AuthorizationException::class);
        app(ApproveReturnAction::class)->execute($inactiveHead, $this->returnRequest);
    }
}
