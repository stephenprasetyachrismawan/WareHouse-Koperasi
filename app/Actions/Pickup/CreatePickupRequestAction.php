<?php

namespace App\Actions\Pickup;

use App\Domain\Pickup\ValueObjects\PickupRequestInput;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CreatePickupRequestAction
{
    public function execute(User $actor, PickupRequestInput $input): PickupRequest
    {
        $isActiveMember = $actor->warehouseMemberships()
            ->where('warehouse_id', $input->warehouseId)
            ->where('status', 'active')
            ->exists();

        if (! $isActiveMember) {
            throw new AuthorizationException('User is not an active member of this warehouse.');
        }

        return DB::transaction(function () use ($input) {
            $requestNumber = sprintf(
                'REQ-%s-%s',
                now()->format('Ymd'),
                strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))
            );

            $pickupRequest = PickupRequest::create([
                'warehouse_id' => $input->warehouseId,
                'user_id' => $input->userId,
                'request_number' => $requestNumber,
                'status' => PickupRequestStatus::Submitted,
                'notes' => $input->notes,
            ]);

            foreach ($input->items as $itemInput) {
                $pickupRequest->items()->create([
                    'item_id' => $itemInput->itemId,
                    'requested_quantity' => $itemInput->quantity,
                    'notes' => $itemInput->notes,
                ]);
            }

            return $pickupRequest;
        });
    }
}
