<?php

namespace Database\Seeders;

use App\Actions\Procurement\CompleteQualityInspectionAction;
use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Actions\Returns\ApproveReturnAction;
use App\Actions\Returns\CreateReturnAction;
use App\Actions\Returns\RejectReturnAction;
use App\Actions\Returns\SubmitReturnForApprovalAction;
use App\Actions\Returns\VerifyReturnAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Domain\Returns\ValueObjects\CreateReturnInput;
use App\Domain\Returns\ValueObjects\VerifyReturnInput;
use App\Enums\PurchaseRequestStatus;
use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Enums\ReturnReasonCode;
use App\Enums\ReturnStatus;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Phase 5.2: progresses the Phase 5.1 WAITING_APPROVAL/ADMIN_VERIFIED demo
 * returns (from DemoReturnSeeder) through Kepala Gudang's decision, plus one
 * fresh still-pending return per warehouse so the Approval Queue has live
 * data. Runs the real Actions so the demo exercises production code paths.
 * Idempotent: every mutation is guarded by checking the current status
 * before acting, so re-running the full seeder chain is always safe.
 */
class DemoReturnDecisionSeeder extends Seeder
{
    public function run(): void
    {
        Gate::before(fn () => true);

        $this->decidePusatScenarios();
        $this->decideBaratScenarios();
    }

    private function decidePusatScenarios(): void
    {
        $koperasi1 = User::where('email', 'koperasi.unit1@koperasi.id')->first();
        $staffAdmin = User::where('email', 'staff.admin@koperasi.id')->first();
        $kepalaGudang = User::where('email', 'kepala.gudang@koperasi.id')->first();
        $warehouse = Warehouse::where('code', 'WH-PUSAT')->first();
        $pickup = PickupRequest::where('request_number', 'REQ-20260801-A101')->first();

        if (! $koperasi1 || ! $staffAdmin || ! $kepalaGudang || ! $warehouse || ! $pickup) {
            return;
        }

        // Scenario C already has QC evidence for BM-2L (from DemoGoodsReceiptSeeder) -> WAREHOUSE attribution.
        $this->approveByMarker($kepalaGudang, 'Demo Seeder Return - Scenario C (Waiting approval) (PUS)');

        // Scenario B (IM-GRG, no QC evidence) -> submit for approval, then reject.
        $this->submitThenRejectByMarker($staffAdmin, $kepalaGudang, 'Demo Seeder Return - Scenario B (Admin verified) (PUS)', 'Foto tidak menunjukkan kerusakan yang meyakinkan.');

        // Scenario D: a fresh, still-undecided return using the last eligible unit of IM-GRG.
        $indomieLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'IM-GRG'))->first();
        if ($indomieLine) {
            $this->submitAndLeavePending(
                $koperasi1,
                $staffAdmin,
                $pickup,
                $indomieLine,
                marker: 'Demo Seeder Return - Scenario D (Waiting approval, pending decision) (PUS)',
            );
        }
    }

    private function decideBaratScenarios(): void
    {
        $koperasi3 = User::where('email', 'koperasi.unit3@koperasi.id')->first();
        $staffBarat = User::where('email', 'staff.barat@koperasi.id')->first();
        $kepalaBarat = User::where('email', 'kepala.barat@koperasi.id')->first();
        $warehouse = Warehouse::where('code', 'WH-BARAT')->first();
        $pickup = PickupRequest::where('request_number', 'REQ-20260802-W101')->first();

        if (! $koperasi3 || ! $staffBarat || ! $kepalaBarat || ! $warehouse || ! $pickup) {
            return;
        }

        // Scenario B (IM-KUH, no QC evidence) -> submit for approval, then approve -> SUPPLIER attribution.
        $this->submitThenApproveByMarker($staffBarat, $kepalaBarat, 'Demo Seeder Return - Scenario B (Admin verified) (BAR)');

        // Scenario C (AQ-600, no QC evidence) -> reject.
        $this->rejectByMarker($kepalaBarat, 'Demo Seeder Return - Scenario C (Waiting approval) (BAR)', 'Barang sudah digunakan sebagian, tidak memenuhi syarat retur.');

        // Scenario D: give IM-KUH a passed QC record, then submit a fresh
        // still-undecided return so the queue also shows a WAREHOUSE-eligible case.
        $indomieKuahItem = Item::where('warehouse_id', $warehouse->id)->where('code', 'IM-KUH')->first();
        if ($indomieKuahItem) {
            $this->ensureItemHasPassedQc($warehouse, $indomieKuahItem, $staffBarat, 'Demo Seeder Receipt - Return QC Evidence (BAR)');
        }

        $indomieKuahLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'IM-KUH'))->first();
        if ($indomieKuahLine) {
            $this->submitAndLeavePending(
                $koperasi3,
                $staffBarat,
                $pickup,
                $indomieKuahLine,
                marker: 'Demo Seeder Return - Scenario D (Waiting approval, pending decision) (BAR)',
            );
        }
    }

    private function findByMarker(string $marker): ?ReturnRequest
    {
        return ReturnRequest::where('reason_notes', 'like', $marker.'%')->first();
    }

    private function approveByMarker(User $head, string $marker): void
    {
        $returnRequest = $this->findByMarker($marker);
        if (! $returnRequest || $returnRequest->status !== ReturnStatus::WaitingApproval) {
            return;
        }

        app(ApproveReturnAction::class)->execute($head, $returnRequest);
    }

    private function rejectByMarker(User $head, string $marker, string $reason): void
    {
        $returnRequest = $this->findByMarker($marker);
        if (! $returnRequest || $returnRequest->status !== ReturnStatus::WaitingApproval) {
            return;
        }

        app(RejectReturnAction::class)->execute($head, $returnRequest, $reason);
    }

    private function submitThenRejectByMarker(User $staff, User $head, string $marker, string $reason): void
    {
        $returnRequest = $this->findByMarker($marker);
        if (! $returnRequest) {
            return;
        }

        if ($returnRequest->status === ReturnStatus::AdminVerified) {
            $returnRequest = app(SubmitReturnForApprovalAction::class)->execute($staff, $returnRequest, $returnRequest->version);
        }

        if ($returnRequest->status === ReturnStatus::WaitingApproval) {
            app(RejectReturnAction::class)->execute($head, $returnRequest, $reason);
        }
    }

    private function submitThenApproveByMarker(User $staff, User $head, string $marker): void
    {
        $returnRequest = $this->findByMarker($marker);
        if (! $returnRequest) {
            return;
        }

        if ($returnRequest->status === ReturnStatus::AdminVerified) {
            $returnRequest = app(SubmitReturnForApprovalAction::class)->execute($staff, $returnRequest, $returnRequest->version);
        }

        if ($returnRequest->status === ReturnStatus::WaitingApproval) {
            app(ApproveReturnAction::class)->execute($head, $returnRequest);
        }
    }

    private function submitAndLeavePending(User $koperasi, User $staff, PickupRequest $pickup, PickupRequestItem $line, string $marker): void
    {
        if (ReturnRequest::where('pickup_request_id', $pickup->id)->where('reason_notes', 'like', $marker.'%')->exists()) {
            return;
        }

        $returnRequest = app(CreateReturnAction::class)->execute($koperasi, new CreateReturnInput(
            warehouseId: $pickup->warehouse_id,
            pickupRequestId: $pickup->id,
            pickupRequestItemId: $line->id,
            returnQuantity: 1,
            reasonCode: ReturnReasonCode::Damaged,
            reasonNotes: $marker,
            evidencePath: 'return-evidence/demo/'.Str::uuid().'.jpg',
            evidenceMime: 'image/jpeg',
        ));

        $barcode = ItemBarcode::where('item_id', $line->item_id)->where('is_primary', true)->first()
            ?? ItemBarcode::where('item_id', $line->item_id)->first();
        if (! $barcode) {
            return;
        }

        $verified = app(VerifyReturnAction::class)->execute($staff, $returnRequest, new VerifyReturnInput(
            warehouseId: $returnRequest->warehouse_id,
            scannedBarcode: $barcode->barcode,
            verifiedQuantity: 1,
            evidencePath: 'return-evidence/demo/'.Str::uuid().'.jpg',
            evidenceMime: 'image/jpeg',
            notes: 'Barang sesuai laporan Koperasi, kondisi telah difoto.',
            expectedVersion: $returnRequest->version,
        ));

        app(SubmitReturnForApprovalAction::class)->execute($staff, $verified, $verified->version);
    }

    private function ensureItemHasPassedQc(Warehouse $warehouse, Item $item, User $actor, string $marker): void
    {
        if (PurchaseRequest::where('notes', 'like', "Demo Seeder PR for {$marker}%")->exists()) {
            return;
        }

        $supplier = Supplier::forWarehouse($warehouse->id)->active()->first();
        if (! $supplier) {
            return;
        }

        $purchaseRequest = PurchaseRequest::create([
            'warehouse_id' => $warehouse->id,
            'request_number' => 'PR-'.now()->format('Ymd').'-'.random_int(10000, 99999),
            'source' => 'MANUAL_STAFF',
            'urgency' => 'NORMAL',
            'status' => PurchaseRequestStatus::Approved->value,
            'created_by' => $actor->id,
            'notes' => "Demo Seeder PR for {$marker}",
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $prItem = $purchaseRequest->items()->create([
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($actor, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: $marker,
            allocations: [new AllocationInput($prItem->id, 10)],
        ));

        $po = app(CreatePurchaseOrderAction::class)->execute($actor, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: $marker,
            items: [['item_id' => $item->id, 'unit_cost' => 8000]],
        ));

        $po = app(SendPurchaseOrderAction::class)->execute($actor, $po)->load('items');

        $receipt = app(RecordGoodsReceiptAction::class)->execute($actor, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $po->id,
            receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
        ));

        app(CompleteQualityInspectionAction::class)->execute($actor, $receipt->items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
            notes: 'QC evidence fixture for Return fault attribution demo.',
        ));
    }
}
