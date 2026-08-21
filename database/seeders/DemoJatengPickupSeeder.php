<?php

namespace Database\Seeders;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Enums\ApprovalStatus;
use App\Enums\MovementType;
use App\Enums\PickupRequestStatus;
use App\Models\Approval;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Full pickup-status coverage for WH-JATENG, exercising every
 * PickupRequestStatus case (including Draft/Checked/Approved, which the
 * original WH-PUSAT/WH-BARAT demo data does not demonstrate), told through
 * three member cooperatives with distinct seasonal stories: a farmer group
 * gearing up for planting season, a fishing group prepping for a harvest
 * festival, and a women's craft group restocking materials.
 */
class DemoJatengPickupSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-JATENG')->first();
        if (! $warehouse) {
            return;
        }

        $koperasiTani = User::where('email', 'koperasi.tani@koperasi.id')->first();
        $koperasiWanita = User::where('email', 'koperasi.wanita@koperasi.id')->first();
        $koperasiNelayan = User::where('email', 'koperasi.nelayan@koperasi.id')->first();
        $kepalaJateng = User::where('email', 'kepala.jateng@koperasi.id')->first();
        $staffJateng = User::where('email', 'staff.jateng@koperasi.id')->first();

        if (! $koperasiTani || ! $koperasiWanita || ! $koperasiNelayan || ! $kepalaJateng || ! $staffJateng) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get()->keyBy('code');
        if ($items->isEmpty()) {
            return;
        }

        $recordStockMovement = new RecordStockMovementAction;

        // 1. COMPLETED — musim tanam sudah dimulai, pupuk dan benih sudah diambil penuh.
        if (isset($items['PUP-UREA'], $items['BNH-PADI'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260801-A101'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiTani->id,
                    'status' => PickupRequestStatus::Completed,
                    'notes' => 'Pengambilan pupuk dan benih untuk musim tanam padi April',
                    'submitted_at' => now()->subDays(6),
                    'approved_at' => now()->subDays(5),
                    'ready_at' => now()->subDays(4),
                    'completed_at' => now()->subDays(3),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $urea = $items['PUP-UREA'];
                $benih = $items['BNH-PADI'];

                $req->items()->create(['item_id' => $urea->id, 'requested_quantity' => 10, 'fulfilled_quantity' => 10, 'shortage_quantity' => 0]);
                $req->items()->create(['item_id' => $benih->id, 'requested_quantity' => 6, 'fulfilled_quantity' => 6, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req->id,
                    'requested_by' => $koperasiTani->id,
                    'approver_id' => $kepalaJateng->id,
                    'status' => ApprovalStatus::Approved,
                    'reason' => 'Disetujui sesuai alokasi musim tanam anggota',
                    'decided_at' => now()->subDays(5),
                ]);

                $this->tryStockOut($recordStockMovement, $warehouse, $urea->id, 10, $staffJateng->id, $req);
                $this->tryStockOut($recordStockMovement, $warehouse, $benih->id, 6, $staffJateng->id, $req);
            }
        }

        // 2. READY_FOR_PICKUP — garam untuk pengasinan ikan sudah siap diambil.
        if (isset($items['GRM-KRS'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260803-B202'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiNelayan->id,
                    'status' => PickupRequestStatus::ReadyForPickup,
                    'notes' => 'Persiapan garam untuk musim pengasinan ikan tangkapan melimpah',
                    'submitted_at' => now()->subDays(3),
                    'approved_at' => now()->subDays(2),
                    'ready_at' => now()->subDay(),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['GRM-KRS']->id, 'requested_quantity' => 8, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req->id,
                    'requested_by' => $koperasiNelayan->id,
                    'approver_id' => $kepalaJateng->id,
                    'status' => ApprovalStatus::Approved,
                    'reason' => 'Disetujui, siap diambil di Rak J-B-01.',
                    'decided_at' => now()->subDays(2),
                ]);
            }
        }

        // 3. APPROVED (resting state, belum ditandai Ready oleh staff) — bambu anyaman.
        if (isset($items['BMB-ANYM'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260805-C303'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiWanita->id,
                    'status' => PickupRequestStatus::Approved,
                    'notes' => 'Bahan baku anyaman untuk pesanan tas kerajinan pameran UMKM',
                    'submitted_at' => now()->subDays(2),
                    'approved_at' => now()->subHours(20),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['BMB-ANYM']->id, 'requested_quantity' => 5, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req->id,
                    'requested_by' => $koperasiWanita->id,
                    'approver_id' => $kepalaJateng->id,
                    'status' => ApprovalStatus::Approved,
                    'reason' => 'Disetujui, menunggu staff menyiapkan barang di gudang.',
                    'decided_at' => now()->subHours(20),
                ]);
            }
        }

        // 4. WAITING_APPROVAL — benih jagung untuk musim tanam kedua.
        if (isset($items['BNH-JGG'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260806-D404'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiTani->id,
                    'status' => PickupRequestStatus::WaitingApproval,
                    'notes' => 'Kebutuhan benih jagung untuk tumpang sari musim kedua',
                    'submitted_at' => now()->subHours(14),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['BNH-JGG']->id, 'requested_quantity' => 3, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 5. BACKORDERED — pewarna alami kerajinan stoknya kosong.
        if (isset($items['PWR-ALAM'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260807-E505'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiWanita->id,
                    'status' => PickupRequestStatus::Backordered,
                    'notes' => 'Pewarna alami untuk finishing tas anyaman pesanan khusus',
                    'submitted_at' => now()->subHours(10),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create([
                    'item_id' => $items['PWR-ALAM']->id,
                    'requested_quantity' => 6,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 6,
                    'notes' => 'Stok kosong, menunggu pembelian dari CV Anyaman Mandiri Salatiga',
                ]);
            }
        }

        // 6. REJECTED — permintaan ikan teri melebihi kuota unit.
        if (isset($items['IKN-TERI'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260808-F606'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiNelayan->id,
                    'status' => PickupRequestStatus::Rejected,
                    'notes' => 'Permintaan ikan teri untuk dijual kembali di luar program koperasi',
                    'submitted_at' => now()->subDays(3),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['IKN-TERI']->id, 'requested_quantity' => 5, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req->id,
                    'requested_by' => $koperasiNelayan->id,
                    'approver_id' => $kepalaJateng->id,
                    'status' => ApprovalStatus::Rejected,
                    'reason' => 'Ikan teri stok kritis, prioritas untuk konsumsi anggota dulu. Ajukan ulang setelah stok pulih.',
                    'decided_at' => now()->subDays(2),
                ]);
            }
        }

        // 7. CANCELLED — koperasi tani membatalkan karena panen tertunda.
        if (isset($items['BR-25KG'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260809-G707'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiTani->id,
                    'status' => PickupRequestStatus::Cancelled,
                    'notes' => 'Distribusi beras hasil panen untuk bazar desa',
                    'cancelled_at' => now()->subHours(3),
                    'cancellation_reason' => 'Bazar desa ditunda karena cuaca, pengambilan dibatalkan',
                    'submitted_at' => now()->subHours(6),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['BR-25KG']->id, 'requested_quantity' => 4, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 8. SUBMITTED — pupuk NPK belum dicek staff.
        if (isset($items['PUP-NPK'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260810-H808'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiTani->id,
                    'status' => PickupRequestStatus::Submitted,
                    'notes' => 'Pupuk NPK susulan untuk lahan yang baru dibuka',
                    'submitted_at' => now()->subHours(2),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['PUP-NPK']->id, 'requested_quantity' => 5, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 9. CHECKED — staff sudah cek ketersediaan ikan asin, belum diputuskan siap/backorder.
        if (isset($items['IKN-ASIN'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260811-I909'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiNelayan->id,
                    'status' => PickupRequestStatus::Checked,
                    'notes' => 'Ikan asin jambal roti untuk pesanan pasar mingguan',
                    'submitted_at' => now()->subHours(4),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['IKN-ASIN']->id, 'requested_quantity' => 3, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 10. PREPARED (dicek & disiapkan staff, belum diajukan approval) — sembako umum.
        if (isset($items['BM-1L'], $items['IM-GRG-J'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260812-J010'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiWanita->id,
                    'status' => PickupRequestStatus::Prepared,
                    'notes' => 'Sembako untuk konsumsi rapat bulanan anggota',
                    'submitted_at' => now()->subHours(5),
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['BM-1L']->id, 'requested_quantity' => 4, 'fulfilled_quantity' => 4, 'shortage_quantity' => 0]);
                $req->items()->create(['item_id' => $items['IM-GRG-J']->id, 'requested_quantity' => 2, 'fulfilled_quantity' => 2, 'shortage_quantity' => 0]);
            }
        }

        // 11. DRAFT — koperasi wanita masih menyusun daftar kebutuhan pameran, belum disubmit.
        if (isset($items['PWR-ALAM'], $items['BMB-ANYM'])) {
            $req = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-JTG-20260813-K111'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasiWanita->id,
                    'status' => PickupRequestStatus::Draft,
                    'notes' => 'Draft kebutuhan bahan untuk pameran UMKM bulan depan, masih disusun',
                ]
            );

            if ($req->wasRecentlyCreated) {
                $req->items()->create(['item_id' => $items['BMB-ANYM']->id, 'requested_quantity' => 8, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }
    }

    private function tryStockOut(RecordStockMovementAction $action, Warehouse $warehouse, int $itemId, int $quantity, int $performedBy, PickupRequest $request): void
    {
        try {
            $action->execute(new StockMovementInput(
                warehouseId: $warehouse->id,
                itemId: $itemId,
                movementType: MovementType::PickupIssue,
                quantity: $quantity,
                performedBy: $performedBy,
                idempotencyKey: "seed-fulfill-{$request->id}-{$itemId}",
                reason: "Pengambilan Koperasi #{$request->request_number}",
                sourceType: PickupRequest::class,
                sourceId: $request->id
            ));
        } catch (\Throwable $e) {
            // Already seeded; safe to ignore.
        }
    }
}
