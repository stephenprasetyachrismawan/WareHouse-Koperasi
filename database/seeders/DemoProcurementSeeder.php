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
        $warehouse = Warehouse::first();
        if (! $warehouse) {
            return;
        }

        $user = User::first();
        $items = Item::where('warehouse_id', $warehouse->id)->take(3)->get();
        if ($items->isEmpty()) {
            return;
        }

        $statuses = [
            PurchaseRequestStatus::Draft,
            PurchaseRequestStatus::WaitingApproval,
            PurchaseRequestStatus::Approved,
            PurchaseRequestStatus::Rejected,
            PurchaseRequestStatus::Cancelled,
        ];

        foreach ($statuses as $index => $status) {
            $pr = PurchaseRequest::create([
                'warehouse_id' => $warehouse->id,
                'request_number' => 'PR-'.now()->format('Ymd').'-'.sprintf('%04d', $index + 1),
                'source' => PurchaseRequestSource::ManualStaff,
                'urgency' => PurchaseRequestUrgency::Normal,
                'status' => $status,
                'created_by' => $user->id,
                'notes' => 'Demo Seeder PR - '.$status->label(),
                'submitted_at' => in_array($status, [PurchaseRequestStatus::WaitingApproval, PurchaseRequestStatus::Approved, PurchaseRequestStatus::Rejected, PurchaseRequestStatus::Cancelled]) ? now() : null,
                'approved_at' => $status === PurchaseRequestStatus::Approved ? now() : null,
                'rejected_at' => $status === PurchaseRequestStatus::Rejected ? now() : null,
                'cancelled_at' => $status === PurchaseRequestStatus::Cancelled ? now() : null,
                'cancellation_reason' => $status === PurchaseRequestStatus::Cancelled ? 'Dibatalkan untuk demo.' : null,
            ]);

            foreach ($items as $item) {
                $pr->items()->create([
                    'item_id' => $item->id,
                    'requested_quantity' => rand(5, 20),
                ]);
            }
        }
    }
}
