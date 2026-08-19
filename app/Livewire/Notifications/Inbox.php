<?php

namespace App\Livewire\Notifications;

use App\Actions\Notifications\MarkAllNotificationsReadAction;
use App\Actions\Notifications\MarkNotificationReadAction;
use App\Domain\Notifications\Queries\InboxNotificationsQuery;
use App\Models\InboxNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Inbox extends Component
{
    use WithPagination;

    public string $filter = 'all';

    /**
     * Realtime delivery only ever triggers a re-render from the database —
     * when viewing page 1, a newly arrived notification simply appears on
     * the next render; deeper pages are left alone rather than shifted.
     */
    #[On('inbox-notification-received')]
    public function refresh(): void
    {
        //
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unread'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function markRead(int $notificationId, MarkNotificationReadAction $action): void
    {
        $notification = InboxNotification::where('id', $notificationId)->first();
        if (! $notification) {
            return;
        }

        $action->execute(Auth::user(), $notification);
    }

    public function markAllRead(MarkAllNotificationsReadAction $action): void
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;
        $action->execute(Auth::user(), $warehouseId);
    }

    public function render(InboxNotificationsQuery $query): View
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $notifications = $query->execute(
            userId: Auth::user()->id,
            warehouseId: $warehouseId,
            unreadOnly: $this->filter === 'unread',
        );

        return view('livewire.notifications.inbox', [
            'notifications' => $notifications,
            'unreadCount' => $query->unreadCount(Auth::user()->id, $warehouseId),
        ]);
    }
}
