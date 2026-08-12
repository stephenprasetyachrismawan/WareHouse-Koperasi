<?php

namespace App\Actions\Notifications;

use App\Domain\Notifications\Events\InboxNotificationCreated;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Models\InboxNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single write path for every inbox notification in the system — no
 * `$user->notify()` calls scattered through UI or business Actions.
 * Idempotent via a DB-level unique(recipient_id, correlation_key)
 * constraint: replaying the same domain event, or two listener workers
 * racing on the same event, both resolve to the same row rather than
 * duplicating it.
 *
 * The persisted row is authoritative and always written first; the
 * best-effort delivery event (realtime broadcast, and eventually push) only
 * fires for a genuinely new row, after commit, and can never cause this
 * write to fail — a broadcast/Reverb outage must never block the caller.
 */
class CreateInboxNotificationAction
{
    public function execute(CreateInboxNotificationInput $input): InboxNotification
    {
        $existing = InboxNotification::where('recipient_id', $input->recipientId)
            ->where('correlation_key', $input->correlationKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        $notification = $this->createRow($input);

        DB::afterCommit(function () use ($notification) {
            $this->dispatchDeliveryEvent($notification);
        });

        return $notification;
    }

    private function createRow(CreateInboxNotificationInput $input): InboxNotification
    {
        try {
            return InboxNotification::create([
                'recipient_id' => $input->recipientId,
                'warehouse_id' => $input->warehouseId,
                'type' => $input->type,
                'title' => $input->title,
                'message' => $input->message,
                'subject_type' => $input->subjectType,
                'subject_id' => $input->subjectId,
                'action_route' => $input->actionRoute,
                'correlation_key' => $input->correlationKey,
                'metadata' => $input->metadata,
            ]);
        } catch (QueryException $e) {
            // Lost the race to a concurrent insert of the same
            // (recipient_id, correlation_key) pair — resolve to that row.
            // No delivery event here: the winning insert already fired one.
            if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')) {
                return InboxNotification::where('recipient_id', $input->recipientId)
                    ->where('correlation_key', $input->correlationKey)
                    ->firstOrFail();
            }

            throw $e;
        }
    }

    private function dispatchDeliveryEvent(InboxNotification $notification): void
    {
        try {
            InboxNotificationCreated::dispatch($notification->id);
        } catch (Throwable $e) {
            // The InboxNotification is already durably committed — a
            // Reverb/broadcast failure here must never surface to the
            // caller or affect business state.
            Log::warning('Failed to dispatch realtime delivery for inbox notification.', [
                'inbox_notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
