<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;

class CreateBackorderPurchaseRequestAction
{
    public function execute(PickupRequest $pickupRequest, Item $item, int $requestedQty, int $shortageQty): ?PurchaseRequest
    {
        return DB::transaction(function () use ($pickupRequest, $item, $shortageQty) {
            // Idempotency check
            $existing = PurchaseRequest::where('pickup_request_id', $pickupRequest->id)
                ->whereHas('items', function ($q) use ($item) {
                    $q->where('item_id', $item->id);
                })->first();

            if ($existing) {
                return $existing;
            }

            $date = now()->format('Ymd');
            $latest = PurchaseRequest::where('request_number', 'like', "PR-{$date}-%")
                ->orderBy('request_number', 'desc')
                ->lockForUpdate()
                ->first();

            $sequence = 1;
            if ($latest) {
                $lastSequence = (int) substr($latest->request_number, -4);
                $sequence = $lastSequence + 1;
            }
            $requestNumber = sprintf('PR-%s-%04d', $date, $sequence);

            $purchaseRequest = PurchaseRequest::create([
                'warehouse_id' => $pickupRequest->warehouse_id,
                'request_number' => $requestNumber,
                'source' => PurchaseRequestSource::CooperativeBackorder,
                'status' => PurchaseRequestStatus::Draft,
                'created_by' => $pickupRequest->user_id,
                'pickup_request_id' => $pickupRequest->id,
            ]);

            $purchaseRequest->items()->create([
                'item_id' => $item->id,
                'requested_quantity' => $shortageQty,
            ]);

            return $purchaseRequest;
        });
    }
}
