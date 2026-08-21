<?php

namespace Database\Seeders;

use App\Actions\Returns\ApproveReturnAction;
use App\Actions\Returns\CreateReturnAction;
use App\Actions\Returns\RejectReturnAction;
use App\Actions\Returns\SubmitReturnForApprovalAction;
use App\Actions\Returns\VerifyReturnAction;
use App\Domain\Returns\ValueObjects\CreateReturnInput;
use App\Domain\Returns\ValueObjects\VerifyReturnInput;
use App\Enums\ReturnReasonCode;
use App\Enums\ReturnStatus;
use App\Models\ItemBarcode;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Progresses the WH-JATENG demo returns through Kepala Gudang's decision,
 * demonstrating BOTH fault-attribution outcomes (FR-32): approving the
 * Scenario C return (PUP-UREA, which already has passed-QC evidence from
 * DemoJatengGoodsReceiptSeeder's deterministic item-ordering fix) yields
 * WAREHOUSE; approving Scenario B (BNH-PADI, no QC evidence) yields
 * SUPPLIER. Also demonstrates a rejection and a still-pending decision.
 */
class DemoJatengReturnDecisionSeeder extends Seeder
{
    public function run(): void
    {
        Gate::before(fn () => true);

        $koperasiTani = User::where('email', 'koperasi.tani@koperasi.id')->first();
        $staffJateng = User::where('email', 'staff.jateng@koperasi.id')->first();
        $kepalaJateng = User::where('email', 'kepala.jateng@koperasi.id')->first();
        $warehouse = Warehouse::where('code', 'WH-JATENG')->first();
        $pickup = PickupRequest::where('request_number', 'REQ-JTG-20260801-A101')->first();

        if (! $koperasiTani || ! $staffJateng || ! $kepalaJateng || ! $warehouse || ! $pickup) {
            return;
        }

        // Scenario C already has QC evidence for PUP-UREA -> WAREHOUSE attribution.
        $this->approveByMarker($kepalaJateng, 'Demo Seeder Return - Scenario C (Waiting approval) (JTG)');

        // Scenario B (BNH-PADI, no QC evidence) -> submit for approval, then approve -> SUPPLIER attribution.
        $this->submitThenApproveByMarker($staffJateng, $kepalaJateng, 'Demo Seeder Return - Scenario B (Admin verified) (JTG)');

        // Scenario R: a fresh return, submitted straight through, then rejected.
        $ureaLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'PUP-UREA'))->first();
        if ($ureaLine) {
            $this->createSubmitAndReject(
                $koperasiTani,
                $staffJateng,
                $kepalaJateng,
                $pickup,
                $ureaLine,
                marker: 'Demo Seeder Return - Scenario R (Rejected) (JTG)',
                rejectReason: 'Kuantitas retur melebihi laporan awal kerusakan, mohon ajukan ulang dengan bukti foto tambahan.',
            );
        }

        // Scenario D: a fresh, still-undecided return using remaining BNH-PADI units.
        $benihLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'BNH-PADI'))->first();
        if ($benihLine) {
            $this->submitAndLeavePending(
                $koperasiTani,
                $staffJateng,
                $pickup,
                $benihLine,
                marker: 'Demo Seeder Return - Scenario D (Waiting approval, pending decision) (JTG)',
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

    private function createSubmitAndReject(
        User $koperasi,
        User $staff,
        User $head,
        PickupRequest $pickup,
        PickupRequestItem $line,
        string $marker,
        string $rejectReason,
    ): void {
        if (ReturnRequest::where('pickup_request_id', $pickup->id)->where('reason_notes', 'like', $marker.'%')->exists()) {
            return;
        }

        $returnRequest = app(CreateReturnAction::class)->execute($koperasi, new CreateReturnInput(
            warehouseId: $pickup->warehouse_id,
            pickupRequestId: $pickup->id,
            pickupRequestItemId: $line->id,
            returnQuantity: 2,
            reasonCode: ReturnReasonCode::Other,
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
            verifiedQuantity: 2,
            evidencePath: 'return-evidence/demo/'.Str::uuid().'.jpg',
            evidenceMime: 'image/jpeg',
            notes: 'Diverifikasi staff, diteruskan ke Kepala Gudang.',
            expectedVersion: $returnRequest->version,
        ));

        $submitted = app(SubmitReturnForApprovalAction::class)->execute($staff, $verified, $verified->version);

        app(RejectReturnAction::class)->execute($head, $submitted, $rejectReason);
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
            returnQuantity: 2,
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
            verifiedQuantity: 2,
            evidencePath: 'return-evidence/demo/'.Str::uuid().'.jpg',
            evidenceMime: 'image/jpeg',
            notes: 'Barang sesuai laporan Koperasi Tani, kondisi telah difoto.',
            expectedVersion: $returnRequest->version,
        ));

        app(SubmitReturnForApprovalAction::class)->execute($staff, $verified, $verified->version);
    }
}
