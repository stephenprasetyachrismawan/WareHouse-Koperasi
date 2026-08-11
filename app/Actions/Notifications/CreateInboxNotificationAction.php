<?php

namespace App\Actions\Notifications;

use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Models\InboxNotification;
use Illuminate\Database\QueryException;

/**
 * The single write path for every inbox notification in the system — no
 * `$user->notify()` calls scattered through UI or business Actions.
 * Idempotent via a DB-level unique(recipient_id, correlation_key)
 * constraint: replaying the same domain event, or two listener workers
 * racing on the same event, both resolve to the same row rather than
 * duplicating it.
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
            if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')) {
                return InboxNotification::where('recipient_id', $input->recipientId)
                    ->where('correlation_key', $input->correlationKey)
                    ->firstOrFail();
            }

            throw $e;
        }
    }
}
