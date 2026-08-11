<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Procurement\Events\CancellationApproved;
use App\Enums\NotificationType;
use App\Models\CancellationRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyCancellationApproved
{
    public function __construct(
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(CancellationApproved $event): void
    {
        try {
            $cancellationRequest = $event->cancellationRequest;

            $this->createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $cancellationRequest->requested_by,
                warehouseId: $cancellationRequest->warehouse_id,
                type: NotificationType::CancellationStatus,
                title: 'Pembatalan Disetujui',
                message: "Permintaan pembatalan Purchase Request {$cancellationRequest->purchaseRequest->request_number} telah disetujui.",
                correlationKey: "cancellation_request:{$cancellationRequest->id}:status",
                subjectType: CancellationRequest::class,
                subjectId: $cancellationRequest->id,
                actionRoute: route('procurement.show', $cancellationRequest->purchaseRequest->uuid),
            ));
        } catch (Throwable $e) {
            Log::error('Failed to create cancellation-approved notification.', [
                'cancellation_request_id' => $event->cancellationRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
