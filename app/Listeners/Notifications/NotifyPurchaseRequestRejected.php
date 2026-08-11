<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Procurement\Events\PurchaseRequestRejected;
use App\Enums\NotificationType;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyPurchaseRequestRejected
{
    public function __construct(
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(PurchaseRequestRejected $event): void
    {
        try {
            $purchaseRequest = $event->purchaseRequest;

            $this->createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $purchaseRequest->created_by,
                warehouseId: $purchaseRequest->warehouse_id,
                type: NotificationType::ApprovalRejected,
                title: 'Purchase Request Ditolak',
                message: "Purchase Request {$purchaseRequest->request_number} ditolak. Alasan: {$event->reason}",
                correlationKey: "purchase_request:{$purchaseRequest->id}:rejected",
                subjectType: PurchaseRequest::class,
                subjectId: $purchaseRequest->id,
                actionRoute: route('procurement.show', $purchaseRequest->uuid),
            ));
        } catch (Throwable $e) {
            Log::error('Failed to create purchase request rejected notification.', [
                'purchase_request_id' => $event->purchaseRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
