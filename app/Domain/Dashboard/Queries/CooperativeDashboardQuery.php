<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Queries;

use App\Domain\Notifications\Queries\InboxNotificationsQuery;
use App\Enums\PickupRequestStatus;
use App\Enums\ReturnStatus;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use App\Models\WarehouseMembership;
use Illuminate\Support\Carbon;

class CooperativeDashboardQuery
{
    public function __construct(
        private readonly InboxNotificationsQuery $inboxNotifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(WarehouseMembership $membership): array
    {
        $warehouseId = $membership->warehouse_id;
        $userId = $membership->user_id;

        $ownedPickup = PickupRequest::query()
            ->where('warehouse_id', $warehouseId)
            ->where('user_id', $userId);

        $ownedReturns = ReturnRequest::query()
            ->where('warehouse_id', $warehouseId)
            ->where('cooperative_membership_id', $membership->id);

        return [
            'warehouse' => $membership->warehouse,
            'updatedAt' => Carbon::now(),
            'readyPickups' => (clone $ownedPickup)
                ->where('status', PickupRequestStatus::ReadyForPickup->value)
                ->latest('ready_at')
                ->limit(5)
                ->get(['id', 'uuid', 'request_number', 'status', 'ready_at']),
            'latestPickup' => (clone $ownedPickup)
                ->latest('submitted_at')
                ->first(['id', 'uuid', 'request_number', 'status', 'submitted_at']),
            'latestReturn' => (clone $ownedReturns)
                ->latest('submitted_at')
                ->first(['id', 'uuid', 'return_number', 'status', 'submitted_at']),
            'replacementReadyCount' => (clone $ownedReturns)
                ->where('status', ReturnStatus::ReadyForRepickup->value)
                ->count(),
            'unreadInboxCount' => $this->inboxNotifications->unreadCount($userId, $warehouseId),
        ];
    }
}
