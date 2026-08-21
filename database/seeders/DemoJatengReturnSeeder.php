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
 * Return submission/verification scenarios for WH-JATENG, built on top of
 * the already-Completed Jateng pickup (REQ-JTG-20260801-A101). Runs the
 * real Actions so the demo exercises production code paths, matching the
 * DemoReturnSeeder precedent. All four ReturnReasonCode values are used
 * across this file and DemoJatengReturnDecisionSeeder.
 */
class DemoJatengReturnSeeder extends Seeder
{
    public function run(): void
    {
        $koperasiTani = User::where('email', 'koperasi.tani@koperasi.id')->first();
        $staffJateng = User::where('email', 'staff.jateng@koperasi.id')->first();
        $pickup = PickupRequest::where('request_number', 'REQ-JTG-20260801-A101')->first();

        if (! $koperasiTani || ! $staffJateng || ! $pickup) {
            return;
        }

        $ureaLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'PUP-UREA'))->first();
        $benihLine = PickupRequestItem::where('pickup_request_id', $pickup->id)
            ->whereHas('item', fn ($q) => $q->where('code', 'BNH-PADI'))->first();

        if (! $ureaLine || ! $benihLine) {
            return;
        }

        // Scenario A: SUBMITTED — 2 karung pupuk urea dilaporkan basah, belum diverifikasi.
        $this->submitReturn(
            $koperasiTani,
            $pickup,
            $ureaLine,
            quantity: 2,
            reason: ReturnReasonCode::Damaged,
            marker: 'Demo Seeder Return - Scenario A (Submitted, awaiting verification) (JTG)',
        );

        // Scenario B: ADMIN_VERIFIED — benih padi ternyata varietas yang salah kirim, sudah diverifikasi staff.
        $returnB = $this->submitReturn(
            $koperasiTani,
            $pickup,
            $benihLine,
            quantity: 2,
            reason: ReturnReasonCode::WrongItem,
            marker: 'Demo Seeder Return - Scenario B (Admin verified) (JTG)',
        );
        if ($returnB) {
            $this->verifyReturn($staffJateng, $returnB, $benihLine->item_id, 2);
        }

        // Scenario C: WAITING_APPROVAL — sisa pupuk urea, cacat kemasan, sudah diverifikasi dan diajukan.
        $returnC = $this->submitReturn(
            $koperasiTani,
            $pickup,
            $ureaLine,
            quantity: 3,
            reason: ReturnReasonCode::Defective,
            marker: 'Demo Seeder Return - Scenario C (Waiting approval) (JTG)',
        );
        if ($returnC) {
            $verified = $this->verifyReturn($staffJateng, $returnC, $ureaLine->item_id, 3);
            if ($verified) {
                app(SubmitReturnForApprovalAction::class)->execute($staffJateng, $verified, $verified->version);
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
            notes: 'Barang sesuai laporan Koperasi Tani, kondisi telah difoto.',
            expectedVersion: $returnRequest->version,
        ));
    }
}
