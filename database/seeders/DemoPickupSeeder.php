<?php

namespace Database\Seeders;

use App\Enums\PickupRequestStatus;
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
        $warehouse = Warehouse::first();
        if (! $warehouse) {
            return;
        }

        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        $items = Item::all();
        if ($items->isEmpty()) {
            return;
        }

        // Generate different states
        $statuses = [
            PickupRequestStatus::Submitted,
            PickupRequestStatus::WaitingApproval,
            PickupRequestStatus::ReadyForPickup,
            PickupRequestStatus::Completed,
            PickupRequestStatus::Backordered,
        ];

        foreach ($statuses as $index => $status) {
            $user = $users->random();

            $reqNumber = sprintf('REQ-%s-%s', now()->format('Ymd'), strtoupper(Str::random(8)));

            $request = PickupRequest::create([
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'request_number' => $reqNumber,
                'status' => $status,
                'notes' => "Seed data for status: {$status->value}",
            ]);

            // Add 1-3 items
            $selectedItems = $items->random(rand(1, 3));
            foreach ($selectedItems as $item) {
                $requestedQty = rand(1, 5);
                $fulfilledQty = 0;
                $shortageQty = 0;

                if ($status === PickupRequestStatus::Completed || $status === PickupRequestStatus::ReadyForPickup) {
                    $fulfilledQty = $requestedQty;
                } elseif ($status === PickupRequestStatus::Backordered) {
                    $shortageQty = $requestedQty;
                }

                $request->items()->create([
                    'item_id' => $item->id,
                    'requested_quantity' => $requestedQty,
                    'fulfilled_quantity' => $fulfilledQty,
                    'shortage_quantity' => $shortageQty,
                    'notes' => 'Seed note',
                ]);
            }
        }
    }
}
