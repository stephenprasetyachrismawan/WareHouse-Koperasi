<?php

namespace App\Policies;

use App\Models\InboxNotification;
use App\Models\User;

class InboxNotificationPolicy
{
    public function view(User $user, InboxNotification $notification): bool
    {
        return $this->isOwnNotification($user, $notification);
    }

    public function markAsRead(User $user, InboxNotification $notification): bool
    {
        return $this->isOwnNotification($user, $notification);
    }

    private function isOwnNotification(User $user, InboxNotification $notification): bool
    {
        if ($notification->recipient_id !== $user->id) {
            return false;
        }

        // Defense in depth: a warehouse-scoped notification also requires
        // the actor to still hold an active membership in that warehouse.
        // Platform notifications (warehouse_id null) skip this check.
        if ($notification->warehouse_id !== null) {
            $hasActiveMembership = $user->warehouseMemberships()
                ->where('warehouse_id', $notification->warehouse_id)
                ->where('status', 'active')
                ->exists();

            if (! $hasActiveMembership) {
                return false;
            }
        }

        return true;
    }
}
