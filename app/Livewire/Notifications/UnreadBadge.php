<?php

namespace App\Livewire\Notifications;

use App\Domain\Notifications\Queries\InboxNotificationsQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class UnreadBadge extends Component
{
    /**
     * Realtime delivery only ever triggers a re-render from the database —
     * the broadcast payload itself is never trusted as the unread count.
     */
    #[On('inbox-notification-received')]
    public function refresh(): void
    {
        //
    }

    public function render(InboxNotificationsQuery $query)
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        return view('livewire.notifications.unread-badge', [
            'unreadCount' => $query->unreadCount(Auth::id(), $warehouseId),
        ]);
    }
}
