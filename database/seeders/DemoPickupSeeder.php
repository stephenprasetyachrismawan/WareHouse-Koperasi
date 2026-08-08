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
        $warehouse = Warehouse::where('code', 'WH-PUSAT')->first();
        if (! $warehouse) {
            return;
        }

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

        // 1. Request 1: COMPLETED (Full flow with stock out ledger)
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

                $req1->items()->create([
                    'item_id' => $itemBimoli->id,
                    'requested_quantity' => 5,
                    'fulfilled_quantity' => 5,
                    'shortage_quantity' => 0,
                    'notes' => 'Pouch 2 liter',
                ]);

                $req1->items()->create([
                    'item_id' => $itemIndomie->id,
                    'requested_quantity' => 2,
                    'fulfilled_quantity' => 2,
                    'shortage_quantity' => 0,
                    'notes' => 'Dus 40 pcs',
                ]);

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

                // Record atomic stock out
                try {
                    $recordStockMovement->execute(new StockMovementInput(
                        warehouseId: $warehouse->id,
                        itemId: $itemBimoli->id,
                        movementType: MovementType::PickupIssue,
                        quantity: 5,
                        performedBy: $staffAdmin->id,
                        idempotencyKey: "seed-fulfill-{$req1->id}-{$itemBimoli->id}",
                        reason: "Pengambilan Koperasi #{$req1->request_number}",
                        sourceType: PickupRequest::class,
                        sourceId: $req1->id
                    ));

                    $recordStockMovement->execute(new StockMovementInput(
                        warehouseId: $warehouse->id,
                        itemId: $itemIndomie->id,
                        movementType: MovementType::PickupIssue,
                        quantity: 2,
                        performedBy: $staffAdmin->id,
                        idempotencyKey: "seed-fulfill-{$req1->id}-{$itemIndomie->id}",
                        reason: "Pengambilan Koperasi #{$req1->request_number}",
                        sourceType: PickupRequest::class,
                        sourceId: $req1->id
                    ));
                } catch (\Throwable $e) {
                    // Ignore duplicate idempotency
                }
            }
        }

        // 2. Request 2: READY_FOR_PICKUP
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
                $req2->items()->create([
                    'item_id' => $items['BR-5KG']->id,
                    'requested_quantity' => 3,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 0,
                ]);

                $req2->items()->create([
                    'item_id' => $items['GL-1KG']->id,
                    'requested_quantity' => 5,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 0,
                ]);

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

        // 3. Request 3: WAITING_APPROVAL
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
                $req3->items()->create([
                    'item_id' => $items['RS-770']->id,
                    'requested_quantity' => 4,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 0,
                ]);

                $req3->items()->create([
                    'item_id' => $items['BR-450']->id,
                    'requested_quantity' => 2,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 0,
                ]);
            }
        }

        // 4. Request 4: BACKORDERED (Shortage detected)
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
                    'shortage_quantity' => 15, // Requested 25, stock only 10
                    'notes' => 'Stok tidak mencukupi (Kekurangan 15 dus)',
                ]);
            }
        }

        // 5. Request 5: REJECTED
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
                $req5->items()->create([
                    'item_id' => $items['RM-KLP']->id,
                    'requested_quantity' => 40,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 0,
                ]);

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

        // 6. Request 6: CANCELLED
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
                $req6->items()->create([
                    'item_id' => $items['KP-KPL']->id,
                    'requested_quantity' => 10,
                    'fulfilled_quantity' => 0,
                    'shortage_quantity' => 0,
                ]);
            }
        }
    }
}
