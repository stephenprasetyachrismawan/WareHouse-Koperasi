<?php

namespace Database\Seeders;

use App\Actions\Returns\CreateReturnAction;
use App\Actions\Returns\SubmitReturnForApprovalAction;
use App\Actions\Returns\VerifyReturnAction;
use App\Domain\Returns\ValueObjects\CreateReturnInput;
use App\Domain\Returns\ValueObjects\VerifyReturnInput;
use App\Enums\ReturnReasonCode;
use App\Models\ItemBarcode;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Realistic Phase 5.1 Return scenarios (SUBMITTED, ADMIN_VERIFIED,
 * WAITING_APPROVAL) built on top of the already-COMPLETED pickups from
 * DemoPickupSeeder, for both warehouses. Runs the real Actions (not raw
 * model creates) so the demo data exercises the exact same code path as
 * production, matching the DemoGoodsReceiptSeeder precedent. Idempotent via
 * a distinguishing marker in reason_notes.
 */
class DemoReturnSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPusatScenarios();
        $this->seedBaratScenarios();
    }

    private function seedPusatScenarios(): void
    {
        $koperasi1 = User::where('email', 'koperasi.unit1@koperasi.id')->first();
        $staffAdmin = User::where('email', 'staff.admin@koperasi.id')->first();
        $pickup = PickupRequest::where('request_number', 'REQ-20260801-A101')->first();

        if (! $koperasi1 || ! $staffAdmin || ! $pickup) {
            return;
        }

        $bimoliLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'BM-2L'))->first();
        $indomieLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'IM-GRG'))->first();

        if (! $bimoliLine || ! $indomieLine) {
            return;
        }

        // Scenario A: SUBMITTED — Koperasi reports 2 damaged Bimoli pouches, not yet verified.
        $this->submitReturn(
            $koperasi1,
            $pickup,
            $bimoliLine,
            quantity: 2,
            reason: ReturnReasonCode::Damaged,
            marker: 'Demo Seeder Return - Scenario A (Submitted, awaiting verification) (PUS)',
        );

        // Scenario B: ADMIN_VERIFIED — 1 dus Indomie was the wrong item, staff has verified it.
        $returnB = $this->submitReturn(
            $koperasi1,
            $pickup,
            $indomieLine,
            quantity: 1,
            reason: ReturnReasonCode::WrongItem,
            marker: 'Demo Seeder Return - Scenario B (Admin verified) (PUS)',
        );
        if ($returnB) {
            $this->verifyReturn($staffAdmin, $returnB, $indomieLine->item_id, 1);
        }

        // Scenario C: WAITING_APPROVAL — remaining 3 Bimoli pouches, verified and handed off.
        $returnC = $this->submitReturn(
            $koperasi1,
            $pickup,
            $bimoliLine,
            quantity: 3,
            reason: ReturnReasonCode::Defective,
            marker: 'Demo Seeder Return - Scenario C (Waiting approval) (PUS)',
        );
        if ($returnC) {
            $verified = $this->verifyReturn($staffAdmin, $returnC, $bimoliLine->item_id, 3);
            if ($verified) {
                app(SubmitReturnForApprovalAction::class)->execute($staffAdmin, $verified, $verified->version);
            }
        }
    }

    private function seedBaratScenarios(): void
    {
        $koperasi3 = User::where('email', 'koperasi.unit3@koperasi.id')->first();
        $staffBarat = User::where('email', 'staff.barat@koperasi.id')->first();
        $pickup = PickupRequest::where('request_number', 'REQ-20260802-W101')->first();

        if (! $koperasi3 || ! $staffBarat || ! $pickup) {
            return;
        }

        $aquaLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'AQ-600'))->first();
        $indomieKuahLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'IM-KUH'))->first();

        if (! $aquaLine || ! $indomieKuahLine) {
            return;
        }

        // Scenario A: SUBMITTED — 1 dus Aqua reported defective (bocor).
        $this->submitReturn(
            $koperasi3,
            $pickup,
            $aquaLine,
            quantity: 1,
            reason: ReturnReasonCode::Defective,
            marker: 'Demo Seeder Return - Scenario A (Submitted, awaiting verification) (BAR)',
        );

        // Scenario B: ADMIN_VERIFIED — 1 dus Indomie Kuah damaged in transit, staff has verified it.
        $returnB = $this->submitReturn(
            $koperasi3,
            $pickup,
            $indomieKuahLine,
            quantity: 1,
            reason: ReturnReasonCode::Damaged,
            marker: 'Demo Seeder Return - Scenario B (Admin verified) (BAR)',
        );
        if ($returnB) {
            $this->verifyReturn($staffBarat, $returnB, $indomieKuahLine->item_id, 1);
        }

        // Scenario C: WAITING_APPROVAL — remaining 3 dus Aqua, verified and handed off.
        $returnC = $this->submitReturn(
            $koperasi3,
            $pickup,
            $aquaLine,
            quantity: 3,
            reason: ReturnReasonCode::Other,
            marker: 'Demo Seeder Return - Scenario C (Waiting approval) (BAR)',
            reasonNotes: 'Kemasan penyok saat pengiriman, koperasi minta ditukar.',
        );
        if ($returnC) {
            $verified = $this->verifyReturn($staffBarat, $returnC, $aquaLine->item_id, 3);
            if ($verified) {
                app(SubmitReturnForApprovalAction::class)->execute($staffBarat, $verified, $verified->version);
            }
        }
    }

    private function submitReturn(
        User $koperasi,
        PickupRequest $pickup,
        PickupRequestItem $line,
        int $quantity,
        ReturnReasonCode $reason,
        string $marker,
        ?string $reasonNotes = null,
    ): ?ReturnRequest {
        if (ReturnRequest::where('pickup_request_id', $pickup->id)->where('reason_notes', 'like', $marker.'%')->exists()) {
            return null;
        }

        return app(CreateReturnAction::class)->execute($koperasi, new CreateReturnInput(
            warehouseId: $pickup->warehouse_id,
            pickupRequestId: $pickup->id,
            pickupRequestItemId: $line->id,
            returnQuantity: $quantity,
            reasonCode: $reason,
            reasonNotes: $reasonNotes ? "{$marker} — {$reasonNotes}" : $marker,
            evidencePath: 'return-evidence/demo/'.Str::uuid().'.jpg',
            evidenceMime: 'image/jpeg',
        ));
    }

    private function verifyReturn(User $staff, ReturnRequest $returnRequest, int $itemId, int $quantity): ?ReturnRequest
    {
        $barcode = ItemBarcode::where('item_id', $itemId)->where('is_primary', true)->first()
            ?? ItemBarcode::where('item_id', $itemId)->first();

        if (! $barcode) {
            return null;
        }

        return app(VerifyReturnAction::class)->execute($staff, $returnRequest, new VerifyReturnInput(
            warehouseId: $returnRequest->warehouse_id,
            scannedBarcode: $barcode->barcode,
            verifiedQuantity: $quantity,
            evidencePath: 'return-evidence/demo/'.Str::uuid().'.jpg',
            evidenceMime: 'image/jpeg',
            notes: 'Barang sesuai laporan Koperasi, kondisi telah difoto.',
            expectedVersion: $returnRequest->version,
        ));
    }
}
