<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Procurement\Events\PurchaseRequestApproved;
use App\Enums\NotificationType;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyPurchaseRequestApproved
{
    public function __construct(
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(PurchaseRequestApproved $event): void
    {
        try {
            $purchaseRequest = $event->purchaseRequest;

            $this->createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $purchaseRequest->created_by,
                warehouseId: $purchaseRequest->warehouse_id,
                type: NotificationType::ApprovalApproved,
                title: 'Purchase Request Disetujui',
                message: "Purchase Request {$purchaseRequest->request_number} telah disetujui.",
                correlationKey: "purchase_request:{$purchaseRequest->id}:approved",
                subjectType: PurchaseRequest::class,
                subjectId: $purchaseRequest->id,
                actionRoute: route('procurement.show', $purchaseRequest->uuid),
            ));
        } catch (Throwable $e) {
            Log::error('Failed to create purchase request approved notification.', [
                'purchase_request_id' => $event->purchaseRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
