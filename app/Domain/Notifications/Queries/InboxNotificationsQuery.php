<?php

namespace App\Domain\Notifications\Queries;

use App\Enums\NotificationType;
use App\Models\InboxNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The only place complex inbox reads should live — never buried directly
 * inside a Livewire component.
 */
class InboxNotificationsQuery
{
    public function execute(
        int $userId,
        ?int $warehouseId,
        bool $unreadOnly = false,
        ?NotificationType $type = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = InboxNotification::forRecipient($userId)
            ->where(function ($q) use ($warehouseId) {
                $q->whereNull('warehouse_id');
                if ($warehouseId) {
                    $q->orWhere('warehouse_id', $warehouseId);
                }
            });

        if ($unreadOnly) {
            $query->unread();
        }

        if ($type) {
            $query->where('type', $type->value);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function unreadCount(int $userId, ?int $warehouseId): int
    {
        return InboxNotification::forRecipient($userId)
            ->unread()
            ->where(function ($q) use ($warehouseId) {
                $q->whereNull('warehouse_id');
                if ($warehouseId) {
                    $q->orWhere('warehouse_id', $warehouseId);
                }
            })
            ->count();
    }
}
