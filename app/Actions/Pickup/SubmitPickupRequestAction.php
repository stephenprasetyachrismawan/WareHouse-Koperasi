<?php

namespace App\Actions\Pickup;

use App\Domain\Pickup\Events\PickupRequestSubmitted;
use App\Domain\Pickup\Events\StockShortageDetected;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitPickupRequestAction
{
    public function execute(User $actor, PickupRequest $pickupRequest): PickupRequest
    {
        return DB::transaction(function () use ($pickupRequest) {
            $hasShortage = false;

            $pickupRequest->load('items.item.stockBalance');

            foreach ($pickupRequest->items as $requestItem) {
                $item = $requestItem->item;
                $availableQuantity = $item->stockBalance ? $item->stockBalance->quantity : 0;

                $shortageQuantity = max(0, $requestItem->requested_quantity - $availableQuantity);

                $requestItem->update([
                    'shortage_quantity' => $shortageQuantity,
                ]);

                if ($shortageQuantity > 0) {
                    $hasShortage = true;
                    event(new StockShortageDetected(
                        $pickupRequest,
                        $item,
                        $requestItem->requested_quantity,
                        $shortageQuantity
                    ));
                }
            }

            if ($hasShortage) {
                $pickupRequest->update(['status' => PickupRequestStatus::Backordered]);
            } else {
                $pickupRequest->update(['status' => PickupRequestStatus::WaitingApproval]);
            }

            event(new PickupRequestSubmitted($pickupRequest));

            return $pickupRequest;
        });
    }
}
