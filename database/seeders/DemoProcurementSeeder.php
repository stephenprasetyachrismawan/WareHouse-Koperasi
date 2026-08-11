<?php

namespace Database\Seeders;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Enums\PurchaseRequestUrgency;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoProcurementSeeder extends Seeder
{
    public function run(): void
    {
        $whPusat = Warehouse::where('code', 'WH-PUSAT')->first();
        if ($whPusat) {
            $this->seedRequests($whPusat, 'staff.admin@koperasi.id', 'PUS');
        }

        $whBarat = Warehouse::where('code', 'WH-BARAT')->first();
        if ($whBarat) {
            $this->seedRequests($whBarat, 'staff.barat@koperasi.id', 'BAR');
        }
    }

    private function seedRequests(Warehouse $warehouse, string $creatorEmail, string $prefix): void
    {
        $user = User::where('email', $creatorEmail)->first() ?? User::first();
        if (! $user) {
            return;
        }

        $items = Item::where('warehouse_id', $warehouse->id)->get();
        if ($items->isEmpty()) {
            return;
        }

        $scenarios = [
            ['status' => PurchaseRequestStatus::Draft, 'source' => PurchaseRequestSource::ManualStaff, 'urgency' => PurchaseRequestUrgency::Low, 'notes' => 'Draft belum diajukan, masih dicek ulang kuantitasnya'],
            ['status' => PurchaseRequestStatus::WaitingApproval, 'source' => PurchaseRequestSource::CriticalStock, 'urgency' => PurchaseRequestUrgency::High, 'notes' => 'Diajukan otomatis karena stok di bawah batas minimum'],
            ['status' => PurchaseRequestStatus::WaitingApproval, 'source' => PurchaseRequestSource::ManualStaff, 'urgency' => PurchaseRequestUrgency::Normal, 'notes' => 'Permintaan rutin restock bulanan'],
            ['status' => PurchaseRequestStatus::Approved, 'source' => PurchaseRequestSource::CooperativeBackorder, 'urgency' => PurchaseRequestUrgency::Emergency, 'notes' => 'Backorder dari permintaan pengambilan koperasi yang kekurangan stok'],
            ['status' => PurchaseRequestStatus::Rejected, 'source' => PurchaseRequestSource::ManualStaff, 'urgency' => PurchaseRequestUrgency::Low, 'notes' => 'Ditolak karena anggaran kuartal sudah terpakai'],
            ['status' => PurchaseRequestStatus::Cancelled, 'source' => PurchaseRequestSource::ManualStaff, 'urgency' => PurchaseRequestUrgency::Normal, 'notes' => 'Dibatalkan, kebutuhan sudah terpenuhi dari stok lain'],
        ];

        foreach ($scenarios as $index => $scenario) {
            $requestNumber = "PR-{$prefix}-".now()->format('Ymd').'-'.sprintf('%04d', $index + 1);

            $pr = PurchaseRequest::firstOrCreate(
                ['request_number' => $requestNumber],
                [
                    'warehouse_id' => $warehouse->id,
                    'source' => $scenario['source'],
                    'urgency' => $scenario['urgency'],
                    'status' => $scenario['status'],
                    'created_by' => $user->id,
                    'notes' => $scenario['notes'],
                    'submitted_at' => in_array($scenario['status'], [PurchaseRequestStatus::WaitingApproval, PurchaseRequestStatus::Approved, PurchaseRequestStatus::Rejected, PurchaseRequestStatus::Cancelled], true) ? now()->subDays(6 - $index) : null,
                    'approved_at' => $scenario['status'] === PurchaseRequestStatus::Approved ? now()->subDays(2) : null,
                    'rejected_at' => $scenario['status'] === PurchaseRequestStatus::Rejected ? now()->subDay() : null,
                    'cancelled_at' => $scenario['status'] === PurchaseRequestStatus::Cancelled ? now()->subHours(6) : null,
                    'cancellation_reason' => $scenario['status'] === PurchaseRequestStatus::Cancelled ? 'Dibatalkan untuk demo.' : null,
                ]
            );

            if (! $pr->wasRecentlyCreated) {
                continue;
            }

            foreach ($items->random(min(3, $items->count())) as $item) {
                $pr->items()->create([
                    'item_id' => $item->id,
                    'requested_quantity' => rand(5, 20),
                ]);
            }
        }
    }
}
