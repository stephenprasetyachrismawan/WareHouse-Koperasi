<?php

namespace App\Listeners\Notifications;

use App\Domain\Notifications\Events\InboxNotificationCreated;
use App\Domain\Notifications\Support\PushEligibilityPolicy;
use App\Jobs\DeliverPushNotificationJob;
use App\Models\DeviceToken;
use App\Models\InboxNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fans out to a queued DeliverPushNotificationJob per active device — never
 * sends push itself. Eligibility is re-checked inside the job too (state
 * may change between dispatch and execution); the check here just avoids
 * queuing work that's obviously not going anywhere.
 */
class DispatchPushDeliveryForEligibleNotification
{
    public function __construct(
        private readonly PushEligibilityPolicy $eligibility,
    ) {}

    public function handle(InboxNotificationCreated $event): void
    {
        try {
            $notification = InboxNotification::find($event->inboxNotificationId);

            if (! $notification || ! $this->eligibility->isEligible($notification)) {
                return;
            }

            DeviceToken::forUser($notification->recipient_id)->active()->get()
                ->each(fn (DeviceToken $deviceToken) => DeliverPushNotificationJob::dispatch($notification->id, $deviceToken->id));
        } catch (Throwable $e) {
            Log::warning('Failed to dispatch push delivery for inbox notification.', [
                'inbox_notification_id' => $event->inboxNotificationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
