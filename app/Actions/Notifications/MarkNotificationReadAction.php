<?php

namespace App\Actions\Notifications;

use App\Models\InboxNotification;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class MarkNotificationReadAction
{
    public function execute(User $actor, InboxNotification $notification): InboxNotification
    {
        Gate::forUser($actor)->authorize('markAsRead', $notification);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }
}
