<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Returns\Events\ReturnRejected;
use App\Enums\NotificationType;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyReturnRejected
{
    public function __construct(
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(ReturnRejected $event): void
    {
        try {
            $returnRequest = $event->returnRequest;
            $recipientId = $returnRequest->cooperativeMembership?->user_id;

            if (! $recipientId) {
                return;
            }

            $this->createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $recipientId,
                warehouseId: $returnRequest->warehouse_id,
                type: NotificationType::ReturnStatus,
                title: 'Retur Ditolak',
                message: "Retur {$returnRequest->return_number} ditolak. Alasan: {$returnRequest->decision_notes}",
                correlationKey: "return_request:{$returnRequest->id}:status",
                subjectType: ReturnRequest::class,
                subjectId: $returnRequest->id,
                actionRoute: route('returns.show', $returnRequest->uuid),
            ));
        } catch (Throwable $e) {
            Log::error('Failed to create return-rejected notification.', [
                'return_request_id' => $event->returnRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
