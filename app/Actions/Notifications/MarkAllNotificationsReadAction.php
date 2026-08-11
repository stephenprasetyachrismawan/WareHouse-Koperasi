<?php

namespace App\Actions\Notifications;

use App\Models\InboxNotification;
use App\Models\User;

class MarkAllNotificationsReadAction
{
    public function execute(User $actor, ?int $warehouseId): int
    {
        return InboxNotification::forRecipient($actor->id)
            ->unread()
            ->where(function ($q) use ($warehouseId) {
                $q->whereNull('warehouse_id');
                if ($warehouseId) {
                    $q->orWhere('warehouse_id', $warehouseId);
                }
            })
            ->update(['read_at' => now()]);
    }
}
