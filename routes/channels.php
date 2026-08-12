<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Private per-recipient notification channel — matches the structure
 * documented in ARCHITECTURE.md §20 (private-user.{id}.notifications),
 * adapted to use the numeric User id since no User uuid column exists.
 *
 * The channel is keyed by recipient identity only (not warehouse); tenant
 * relevance is enforced downstream by InboxNotificationsQuery's own
 * warehouse scoping and by the broadcast payload's warehouse_id, which the
 * client uses to decide whether a given event is relevant to the currently
 * active warehouse. The hard security boundary here is recipient identity:
 * a user may only ever subscribe to their own channel.
 */
Broadcast::channel('user.{id}.notifications', function (User $user, string $id) {
    if (! ctype_digit($id) || (int) $user->id !== (int) $id) {
        return false;
    }

    return $user->isActive();
});
