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

class DemoPickupSeeder extends Seeder
{
    public function run(): void
    {
        $whPusat = Warehouse::where('code', 'WH-PUSAT')->first();
        if ($whPusat) {
            $this->seedPusatScenarios($whPusat);
        }

        $whBarat = Warehouse::where('code', 'WH-BARAT')->first();
        if ($whBarat) {
            $this->seedBaratScenarios($whBarat);
        }
    }

    private function seedPusatScenarios(Warehouse $warehouse): void
    {
        $koperasi1 = User::where('email', 'koperasi.unit1@koperasi.id')->first();
        $koperasi2 = User::where('email', 'koperasi.unit2@koperasi.id')->first();
        $kepalaGudang = User::where('email', 'kepala.gudang@koperasi.id')->first();
        $staffAdmin = User::where('email', 'staff.admin@koperasi.id')->first();

        if (! $koperasi1 || ! $koperasi2 || ! $kepalaGudang || ! $staffAdmin) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get()->keyBy('code');
        if ($items->isEmpty()) {
            return;
        }

        $recordStockMovement = new RecordStockMovementAction;

        // 1. COMPLETED (full flow with stock-out ledger)
        if (isset($items['BM-2L'], $items['IM-GRG'])) {
            $req1 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260801-A101'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi1->id,
                    'status' => PickupRequestStatus::Completed,
                    'notes' => 'Pengambilan bahan konsumsi rutin bulanan unit 1',
                    'submitted_at' => now()->subDays(5),
                    'approved_at' => now()->subDays(4),
                    'ready_at' => now()->subDays(3),
                    'completed_at' => now()->subDays(2),
                ]
            );

            if ($req1->wasRecentlyCreated) {
                $itemBimoli = $items['BM-2L'];
                $itemIndomie = $items['IM-GRG'];

                $req1->items()->create(['item_id' => $itemBimoli->id, 'requested_quantity' => 5, 'fulfilled_quantity' => 5, 'shortage_quantity' => 0, 'notes' => 'Pouch 2 liter']);
                $req1->items()->create(['item_id' => $itemIndomie->id, 'requested_quantity' => 2, 'fulfilled_quantity' => 2, 'shortage_quantity' => 0, 'notes' => 'Dus 40 pcs']);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req1->id,
                    'requested_by' => $koperasi1->id,
                    'approver_id' => $kepalaGudang->id,
                    'status' => ApprovalStatus::Approved,
                    'reason' => 'Disetujui sesuai kuota bulanan unit',
                    'decided_at' => now()->subDays(4),
                ]);

                $this->tryStockOut($recordStockMovement, $warehouse, $itemBimoli->id, 5, $staffAdmin->id, $req1);
                $this->tryStockOut($recordStockMovement, $warehouse, $itemIndomie->id, 2, $staffAdmin->id, $req1);
            }
        }

        // 2. READY_FOR_PICKUP
        if (isset($items['BR-5KG'], $items['GL-1KG'])) {
            $req2 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260803-B202'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi2->id,
                    'status' => PickupRequestStatus::ReadyForPickup,
                    'notes' => 'Pengambilan pasokan sembako unit 2',
                    'submitted_at' => now()->subDays(3),
                    'approved_at' => now()->subDays(2),
                    'ready_at' => now()->subDay(),
                ]
            );

            if ($req2->wasRecentlyCreated) {
                $req2->items()->create(['item_id' => $items['BR-5KG']->id, 'requested_quantity' => 3, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
                $req2->items()->create(['item_id' => $items['GL-1KG']->id, 'requested_quantity' => 5, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req2->id,
                    'requested_by' => $koperasi2->id,
                    'approver_id' => $kepalaGudang->id,
                    'status' => ApprovalStatus::Approved,
                    'reason' => 'Disetujui. Siap diambil di Lokasi Rak A-01.',
                    'decided_at' => now()->subDays(2),
                ]);
            }
        }

        // 3. WAITING_APPROVAL
        if (isset($items['RS-770'], $items['BR-450'])) {
            $req3 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260805-C303'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi1->id,
                    'status' => PickupRequestStatus::WaitingApproval,
                    'notes' => 'Kebutuhan perlengkapan kebersihan fasilitas',
                    'submitted_at' => now()->subHours(12),
                ]
            );

            if ($req3->wasRecentlyCreated) {
                $req3->items()->create(['item_id' => $items['RS-770']->id, 'requested_quantity' => 4, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
                $req3->items()->create(['item_id' => $items['BR-450']->id, 'requested_quantity' => 2, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 4. BACKORDERED (shortage detected)
        if (isset($items['AQ-600'])) {
            $req4 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260806-D404'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi2->id,
                    'status' => PickupRequestStatus::Backordered,
                    'notes' => 'Permintaan dus air mineral acara rapat tahunan',
                    'submitted_at' => now()->subHours(6),
                ]
            );

            if ($req4->wasRecentlyCreated) {
                $req4->items()->create([
                    'item_id' => $items['AQ-600']->id,
                    'requested_quantity' => 25,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 15,
                    'notes' => 'Stok tidak mencukupi (Kekurangan 15 dus)',
                ]);
            }
        }

        // 5. REJECTED
        if (isset($items['RM-KLP'])) {
            $req5 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260807-E505'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi1->id,
                    'status' => PickupRequestStatus::Rejected,
                    'notes' => 'Permintaan biskuit event',
                    'submitted_at' => now()->subDays(4),
                ]
            );

            if ($req5->wasRecentlyCreated) {
                $req5->items()->create(['item_id' => $items['RM-KLP']->id, 'requested_quantity' => 40, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req5->id,
                    'requested_by' => $koperasi1->id,
                    'approver_id' => $kepalaGudang->id,
                    'status' => ApprovalStatus::Rejected,
                    'reason' => 'Kuantitas melebihi batas kuota pengeluaran bulanan unit koperasi. Harap ajukan re-evaluasi kuota terlebih dahulu.',
                    'decided_at' => now()->subDays(3),
                ]);
            }
        }

        // 6. CANCELLED
        if (isset($items['KP-KPL'])) {
            $req6 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260808-F606'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi2->id,
                    'status' => PickupRequestStatus::Cancelled,
                    'notes' => 'Permintaan pasokan kopi instan',
                    'cancelled_at' => now()->subHours(2),
                    'cancellation_reason' => 'Dibatalkan oleh pemohon karena perubahan jadwal kegiatan internal koperasi',
                    'submitted_at' => now()->subHours(4),
                ]
            );

            if ($req6->wasRecentlyCreated) {
                $req6->items()->create(['item_id' => $items['KP-KPL']->id, 'requested_quantity' => 10, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 7. SUBMITTED (not yet checked by Staff Admin)
        if (isset($items['TS-250'], $items['PG-190'])) {
            $req7 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260809-G707'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi1->id,
                    'status' => PickupRequestStatus::Submitted,
                    'notes' => 'Kebutuhan tisu dan pasta gigi untuk mess karyawan',
                    'submitted_at' => now()->subHours(1),
                ]
            );

            if ($req7->wasRecentlyCreated) {
                $req7->items()->create(['item_id' => $items['TS-250']->id, 'requested_quantity' => 6, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
                $req7->items()->create(['item_id' => $items['PG-190']->id, 'requested_quantity' => 8, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 8. PREPARED (checked & staged, awaiting approval submission)
        if (isset($items['HVS-A4'], $items['PLP-STD'])) {
            $req8 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260810-H808'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi2->id,
                    'status' => PickupRequestStatus::Prepared,
                    'notes' => 'Alat tulis kantor untuk keperluan rapat tahunan koperasi',
                    'submitted_at' => now()->subHours(8),
                ]
            );

            if ($req8->wasRecentlyCreated) {
                $req8->items()->create(['item_id' => $items['HVS-A4']->id, 'requested_quantity' => 5, 'fulfilled_quantity' => 5, 'shortage_quantity' => 0]);
                $req8->items()->create(['item_id' => $items['PLP-STD']->id, 'requested_quantity' => 3, 'fulfilled_quantity' => 3, 'shortage_quantity' => 0]);
            }
        }
    }

    private function seedBaratScenarios(Warehouse $warehouse): void
    {
        $koperasi3 = User::where('email', 'koperasi.unit3@koperasi.id')->first();
        $koperasi4 = User::where('email', 'koperasi.unit4@koperasi.id')->first();
        $kepalaBarat = User::where('email', 'kepala.barat@koperasi.id')->first();
        $staffBarat = User::where('email', 'staff.barat@koperasi.id')->first();

        if (! $koperasi3 || ! $koperasi4 || ! $kepalaBarat || ! $staffBarat) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get()->keyBy('code');
        if ($items->isEmpty()) {
            return;
        }

        $recordStockMovement = new RecordStockMovementAction;

        // 1. COMPLETED
        if (isset($items['AQ-600'], $items['IM-KUH'])) {
            $req1 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260802-W101'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi3->id,
                    'status' => PickupRequestStatus::Completed,
                    'notes' => 'Pengambilan konsumsi rapat anggota unit simpan pinjam',
                    'submitted_at' => now()->subDays(6),
                    'approved_at' => now()->subDays(5),
                    'ready_at' => now()->subDays(4),
                    'completed_at' => now()->subDays(3),
                ]
            );

            if ($req1->wasRecentlyCreated) {
                $itemAqua = $items['AQ-600'];
                $itemIndomieKuah = $items['IM-KUH'];

                $req1->items()->create(['item_id' => $itemAqua->id, 'requested_quantity' => 4, 'fulfilled_quantity' => 4, 'shortage_quantity' => 0]);
                $req1->items()->create(['item_id' => $itemIndomieKuah->id, 'requested_quantity' => 3, 'fulfilled_quantity' => 3, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req1->id,
                    'requested_by' => $koperasi3->id,
                    'approver_id' => $kepalaBarat->id,
                    'status' => ApprovalStatus::Approved,
                    'reason' => 'Disetujui sesuai kuota unit',
                    'decided_at' => now()->subDays(5),
                ]);

                $this->tryStockOut($recordStockMovement, $warehouse, $itemAqua->id, 4, $staffBarat->id, $req1);
                $this->tryStockOut($recordStockMovement, $warehouse, $itemIndomieKuah->id, 3, $staffBarat->id, $req1);
            }
        }

        // 2. BACKORDERED — RS-770 has zero stock at WH-BARAT by design.
        if (isset($items['RS-770'])) {
            $req2 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260804-W202'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi4->id,
                    'status' => PickupRequestStatus::Backordered,
                    'notes' => 'Kebutuhan deterjen untuk kegiatan bersih-bersih fasilitas cabang',
                    'submitted_at' => now()->subHours(20),
                ]
            );

            if ($req2->wasRecentlyCreated) {
                $req2->items()->create([
                    'item_id' => $items['RS-770']->id,
                    'requested_quantity' => 6,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 6,
                    'notes' => 'Stok kosong di Gudang Barat, menunggu pembelian',
                ]);
            }
        }

        // 3. WAITING_APPROVAL
        if (isset($items['TP-1KG'], $items['GR-500'])) {
            $req3 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260809-W303'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi3->id,
                    'status' => PickupRequestStatus::WaitingApproval,
                    'notes' => 'Kebutuhan dapur mess karyawan cabang barat',
                    'submitted_at' => now()->subHours(5),
                ]
            );

            if ($req3->wasRecentlyCreated) {
                $req3->items()->create(['item_id' => $items['TP-1KG']->id, 'requested_quantity' => 3, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
                $req3->items()->create(['item_id' => $items['GR-500']->id, 'requested_quantity' => 2, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);
            }
        }

        // 4. READY_FOR_PICKUP
        if (isset($items['SKM-FF'])) {
            $req4 = PickupRequest::firstOrCreate(
                ['request_number' => 'REQ-20260810-W404'],
                [
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $koperasi4->id,
                    'status' => PickupRequestStatus::ReadyForPickup,
                    'notes' => 'Susu kental manis untuk kantin koperasi',
                    'submitted_at' => now()->subDays(2),
                    'approved_at' => now()->subDay(),
                    'ready_at' => now()->subHours(3),
                ]
            );

            if ($req4->wasRecentlyCreated) {
                $req4->items()->create(['item_id' => $items['SKM-FF']->id, 'requested_quantity' => 5, 'fulfilled_quantity' => 0, 'shortage_quantity' => 0]);

                Approval::create([
                    'uuid' => (string) Str::uuid(),
                    'warehouse_id' => $warehouse->id,
                    'approvable_type' => PickupRequest::class,
                    'approvable_id' => $req4->id,
                    'requested_by' => $koperasi4->id,
                    'approver_id' => $kepalaBarat->id,
                    'status' => ApprovalStatus::Approved,
                    'reason' => 'Disetujui, siap diambil di Rak B-02 Gudang Barat.',
                    'decided_at' => now()->subDay(),
                ]);
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
