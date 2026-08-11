<?php

namespace App\Livewire\Notifications;

use App\Actions\Notifications\MarkAllNotificationsReadAction;
use App\Actions\Notifications\MarkNotificationReadAction;
use App\Domain\Notifications\Queries\InboxNotificationsQuery;
use App\Models\InboxNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Inbox extends Component
{
    use WithPagination;

    public string $filter = 'all';

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

    public function render(InboxNotificationsQuery $query)
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $notifications = $query->execute(
            userId: Auth::id(),
            warehouseId: $warehouseId,
            unreadOnly: $this->filter === 'unread',
        );

        return view('livewire.notifications.inbox', [
            'notifications' => $notifications,
            'unreadCount' => $query->unreadCount(Auth::id(), $warehouseId),
        ]);
    }
}
