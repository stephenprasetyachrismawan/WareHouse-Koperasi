<?php

namespace App\Domain\Notifications\Events;

use App\Models\InboxNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A persisted InboxNotification became available. Carries only the id
 * needed to resolve the authoritative record — never a serialised model
 * graph. Delivery handlers (broadcast listener, push eligibility) reload
 * the InboxNotification themselves rather than trusting event payload data,
 * so the persisted record always remains the source of truth.
 *
 * This event does not decide business outcomes — it only announces that one
 * already happened and was durably recorded.
 */
class InboxNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $inboxNotificationId,
    ) {}

    public function broadcastOn(): array
    {
        $notification = InboxNotification::find($this->inboxNotificationId);

        if (! $notification) {
            return [];
        }

        return [
            new PrivateChannel("user.{$notification->recipient_id}.notifications"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Minimal, recipient-safe payload only — the same content Phase 6.1
     * already decided was safe to persist. No metadata, no raw evidence
     * paths, no fault-attribution internals.
     */
    public function broadcastWith(): array
    {
        $notification = InboxNotification::find($this->inboxNotificationId);

        if (! $notification) {
            return [];
        }

        return [
            'uuid' => $notification->uuid,
            'type' => $notification->type->value,
            'title' => $notification->title,
            'message' => $notification->message,
            'action_route' => $notification->action_route,
            'warehouse_id' => $notification->warehouse_id,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
