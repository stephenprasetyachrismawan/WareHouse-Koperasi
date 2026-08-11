<?php

namespace Tests\Feature\Returns;

use App\Actions\Procurement\CompleteQualityInspectionAction;
use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\PurchaseRequestStatus;
use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Enums\ReturnStatus;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReturnReplacementProcurementFallbackTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Item $item;

    private Supplier $supplier;

    private User $purchasing;

    private User $koperasi;

    private ReturnRequest $returnRequest;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->warehouse = Warehouse::factory()->create();
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        $this->supplier = Supplier::factory()->create(['warehouse_id' => $this->warehouse->id, 'is_active' => true]);
        $this->purchasing = User::factory()->create();

        $this->koperasi = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $this->koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'active',
        ]);

        // No stock at all -> shortage triggers the procurement fallback.
        $this->returnRequest = ReturnRequest::factory()->replacementPending()->create([
            'warehouse_id' => $this->warehouse->id,
            'cooperative_membership_id' => $membership->id,
            'submitted_by' => $this->koperasi->id,
        ]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'item_id' => $this->item->id,
            'return_quantity' => 5,
        ]);
    }

    public function test_procurement_completion_wakes_the_return_and_prepares_replacement(): void
    {
        $result = app(PrepareReplacementPickupAction::class)->execute($this->returnRequest);
        $this->assertEquals(ReturnStatus::ReplacementPending, $result->status);

        $pr = PurchaseRequest::where('return_request_id', $this->returnRequest->id)->first();
        $this->assertNotNull($pr);
        $this->assertEquals(5, $pr->items->first()->requested_quantity);

        $this->runProcurementToCompletion($pr, QualityInspectionResult::Pass);

        $refreshed = $this->returnRequest->fresh();
        $this->assertEquals(ReturnStatus::ReadyForRepickup, $refreshed->status);
        $this->assertNotNull($refreshed->replacement_pickup_request_id);
    }

    public function test_qc_failure_during_replacement_procurement_leaves_return_pending(): void
    {
        app(PrepareReplacementPickupAction::class)->execute($this->returnRequest);
        $pr = PurchaseRequest::where('return_request_id', $this->returnRequest->id)->first();

        $this->runProcurementToCompletion($pr, QualityInspectionResult::Fail);

        $refreshed = $this->returnRequest->fresh();
        $this->assertEquals(ReturnStatus::ReplacementPending, $refreshed->status);
        $this->assertNull($refreshed->replacement_pickup_request_id);
    }

    public function test_rejected_replacement_procurement_leaves_return_pending(): void
    {
        app(PrepareReplacementPickupAction::class)->execute($this->returnRequest);
        $pr = PurchaseRequest::where('return_request_id', $this->returnRequest->id)->first();

        $pr->update(['status' => PurchaseRequestStatus::Rejected]);

        $refreshed = $this->returnRequest->fresh();
        $this->assertEquals(ReturnStatus::ReplacementPending, $refreshed->status);
        $this->assertNull($refreshed->replacement_pickup_request_id);
    }

    private function runProcurementToCompletion(PurchaseRequest $pr, QualityInspectionResult $result): void
    {
        $prItem = $pr->items->first();
        $pr->update(['status' => PurchaseRequestStatus::Approved->value]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($this->purchasing, new CreateGroupInput(
            warehouseId: $this->warehouse->id,
            notes: 'Replacement fallback group',
            allocations: [new AllocationInput($prItem->id, $prItem->requested_quantity)],
        ));

        $po = app(CreatePurchaseOrderAction::class)->execute($this->purchasing, new CreatePurchaseOrderInput(
            warehouseId: $this->warehouse->id,
            groupId: $group->id,
            supplierId: $this->supplier->id,
            notes: 'Replacement fallback PO',
            items: [['item_id' => $this->item->id, 'unit_cost' => 5000]],
        ));

        $po = app(SendPurchaseOrderAction::class)->execute($this->purchasing, $po)->load('items');

        $receipt = app(RecordGoodsReceiptAction::class)->execute($this->purchasing, new RecordGoodsReceiptInput(
            warehouseId: $this->warehouse->id,
            purchaseOrderId: $po->id,
            receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
        ));

        app(CompleteQualityInspectionAction::class)->execute($this->purchasing, $receipt->items->first(), new CompleteQualityInspectionInput(
            warehouseId: $this->warehouse->id,
            result: $result,
            condition: $result === QualityInspectionResult::Pass ? QualityInspectionCondition::Good : QualityInspectionCondition::Damaged,
            notes: $result === QualityInspectionResult::Fail ? 'Rusak saat pemeriksaan.' : null,
        ));
    }
}
