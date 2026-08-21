<?php

namespace Database\Seeders;

use App\Actions\Procurement\ApprovePurchaseCancellationAction;
use App\Actions\Procurement\RejectPurchaseCancellationAction;
use App\Actions\Procurement\RequestPurchaseCancellationAction;
use App\Enums\PurchaseRequestStatus;
use App\Enums\PurchaseRequestUrgency;
use App\Models\CancellationRequest;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Gate;

/**
 * Purchase-request-level status coverage for WH-JATENG, plus the first demo
 * coverage anywhere in this repo of the Cancellation Request workflow
 * (pending / approved / rejected) — the earlier WH-PUSAT/WH-BARAT seeders
 * never exercised it. Story: Gudang Koperasi Jateng restocking ahead of
 * planting season, with one order getting cancelled after prices changed.
 */
class DemoJatengProcurementSeeder extends Seeder
{
    public function run(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::where('code', 'WH-JATENG')->first();
        $staffJateng = User::where('email', 'staff.jateng@koperasi.id')->first();
        $kepalaJateng = User::where('email', 'kepala.jateng@koperasi.id')->first();

        if (! $warehouse || ! $staffJateng || ! $kepalaJateng) {
            return;
        }

        // 1. DRAFT — belum diajukan, Staff Admin masih menghitung kebutuhan pupuk musim depan.
        $this->makePurchaseRequest($warehouse, $staffJateng, 'PR-JTG-20260801-A101', PurchaseRequestStatus::Draft, PurchaseRequestUrgency::Normal,
            'Draft rencana pembelian pupuk untuk musim tanam berikutnya, masih dihitung ulang');

        // 2. WAITING_APPROVAL — kebutuhan benih jagung urgensi tinggi.
        $this->makePurchaseRequest($warehouse, $staffJateng, 'PR-JTG-20260803-B202', PurchaseRequestStatus::WaitingApproval, PurchaseRequestUrgency::High,
            'Benih jagung tambahan, musim tanam sudah dekat', submittedAt: now()->subHours(18));

        // 3. APPROVED (belum dikelompokkan ke PO) — garam untuk koperasi nelayan.
        $prApproved = $this->makePurchaseRequest($warehouse, $staffJateng, 'PR-JTG-20260804-C303', PurchaseRequestStatus::Approved, PurchaseRequestUrgency::Normal,
            'Garam krosok untuk musim pengasinan ikan', submittedAt: now()->subDays(2), approvedAt: now()->subDay());

        // 4. REJECTED — permintaan bahan kerajinan melebihi anggaran unit.
        $this->makePurchaseRequest($warehouse, $staffJateng, 'PR-JTG-20260805-D404', PurchaseRequestStatus::Rejected, PurchaseRequestUrgency::Low,
            'Bahan kerajinan tambahan untuk koleksi baru', submittedAt: now()->subDays(4));

        // 5. CANCELLED (dibatalkan langsung sebelum approval, bukan lewat alur cancellation request)
        $this->makePurchaseRequest($warehouse, $staffJateng, 'PR-JTG-20260806-E505', PurchaseRequestStatus::Cancelled, PurchaseRequestUrgency::Emergency,
            'Pupuk darurat akibat serangan hama, dibatalkan karena hama sudah teratasi lebih cepat', submittedAt: now()->subDays(5),
            cancelledAt: now()->subDays(4), cancellationReason: 'Serangan hama teratasi dengan metode alternatif, pembelian darurat tidak lagi diperlukan');

        // --- Cancellation Request workflow (new coverage) ---

        // 6. WAITING_APPROVAL + CancellationRequest PENDING — masih menunggu keputusan Kepala Gudang.
        $prPendingCancel = $this->makePurchaseRequest($warehouse, $staffJateng, 'PR-JTG-20260807-F606', PurchaseRequestStatus::WaitingApproval, PurchaseRequestUrgency::Normal,
            'Benih padi cadangan untuk lahan sewaan baru', submittedAt: now()->subHours(10));

        if (! CancellationRequest::where('purchase_request_id', $prPendingCancel->id)->exists()) {
            app(RequestPurchaseCancellationAction::class)->execute(
                $staffJateng,
                $prPendingCancel,
                'Lahan sewaan batal digarap musim ini, benih tidak lagi diperlukan'
            );
        }

        // 7. Cancellation APPROVED — purchase request berakhir Cancelled lewat alur resmi.
        $prToCancel = $this->makePurchaseRequest($warehouse, $staffJateng, 'PR-JTG-20260808-G707', PurchaseRequestStatus::Approved, PurchaseRequestUrgency::High,
            'Pupuk NPK tambahan, harga sempat naik drastis', submittedAt: now()->subDays(3), approvedAt: now()->subDays(2));

        if ($prToCancel->status !== PurchaseRequestStatus::Cancelled) {
            $existingCancellation = CancellationRequest::where('purchase_request_id', $prToCancel->id)->first();
            if (! $existingCancellation) {
                $cancellation = app(RequestPurchaseCancellationAction::class)->execute(
                    $staffJateng,
                    $prToCancel,
                    'Harga pupuk NPK naik 40% dari supplier utama, koperasi mencari alternatif harga lebih baik'
                );
                app(ApprovePurchaseCancellationAction::class)->execute(
                    $kepalaJateng,
                    $cancellation,
                    'Disetujui, cari penawaran dari supplier lain dulu'
                );
            }
        }

        // 8. Cancellation REJECTED — purchase request tetap lanjut (status semula dipertahankan).
        if ($prApproved->status !== PurchaseRequestStatus::Cancelled) {
            $existingCancellation = CancellationRequest::where('purchase_request_id', $prApproved->id)->first();
            if (! $existingCancellation) {
                $cancellation = app(RequestPurchaseCancellationAction::class)->execute(
                    $staffJateng,
                    $prApproved,
                    'Koperasi nelayan minta ditunda, khawatir gudang penuh sebelum musim melaut'
                );
                app(RejectPurchaseCancellationAction::class)->execute(
                    $kepalaJateng,
                    $cancellation,
                    'Garam tetap perlu disiapkan sekarang, musim pengasinan sudah di depan mata'
                );
            }
        }
    }

    private function makePurchaseRequest(
        Warehouse $warehouse,
        User $createdBy,
        string $requestNumber,
        PurchaseRequestStatus $status,
        PurchaseRequestUrgency $urgency,
        string $notes,
        ?CarbonInterface $submittedAt = null,
        ?CarbonInterface $approvedAt = null,
        ?CarbonInterface $cancelledAt = null,
        ?string $cancellationReason = null,
    ): PurchaseRequest {
        $pr = PurchaseRequest::firstOrCreate(
            ['request_number' => $requestNumber],
            [
                'warehouse_id' => $warehouse->id,
                'created_by' => $createdBy->id,
                'source' => 'MANUAL_STAFF',
                'urgency' => $urgency->value,
                'status' => $status->value,
                'notes' => $notes,
                'submitted_at' => $submittedAt,
                'approved_at' => $approvedAt,
                'cancelled_at' => $cancelledAt,
                'cancellation_reason' => $cancellationReason,
            ]
        );

        if ($pr->wasRecentlyCreated) {
            $item = Item::where('warehouse_id', $warehouse->id)->orderBy('id')->first();
            if ($item) {
                $pr->items()->create(['item_id' => $item->id, 'requested_quantity' => 5]);
            }
        }

        return $pr;
    }
}
