<?php

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\MarkNotificationReadAction;
use App\Http\Controllers\Controller;
use App\Models\InboxNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * The only safe way to follow a push notification: authenticate → load the
 * authoritative InboxNotification by UUID → the same Policy the Inbox page
 * already enforces → redirect to its server-resolved action_route. A push
 * payload's data is never itself a permission — it only ever carries a
 * UUID to look up here.
 */
class NotificationDeepLinkController extends Controller
{
    public function show(InboxNotification $inboxNotification, MarkNotificationReadAction $markRead): RedirectResponse
    {
        Gate::authorize('view', $inboxNotification);

        $markRead->execute(Auth::user(), $inboxNotification);

        return redirect($inboxNotification->action_route ?? route('inbox'));
    }
}
