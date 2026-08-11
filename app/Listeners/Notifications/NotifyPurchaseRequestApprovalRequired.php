<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Procurement\Events\PurchaseRequestSubmitted;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A notification failure must never abort the business transaction that
 * triggered it, regardless of whether the event was dispatched before or
 * after commit — every notification listener in this domain wraps its work
 * in try/catch for exactly that reason.
 */
class NotifyPurchaseRequestApprovalRequired
{
    public function __construct(
        private readonly RecipientResolver $recipients,
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(PurchaseRequestSubmitted $event): void
    {
        try {
            $purchaseRequest = $event->purchaseRequest;

            $heads = $this->recipients->warehouseUsersWithPermission(
                $purchaseRequest->warehouse_id,
                Permission::PurchaseRequestApprove,
            );

            foreach ($heads as $head) {
                $this->createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $head->id,
                    warehouseId: $purchaseRequest->warehouse_id,
                    type: NotificationType::ApprovalRequired,
                    title: 'Persetujuan Purchase Request Diperlukan',
                    message: "Purchase Request {$purchaseRequest->request_number} menunggu persetujuan Anda.",
                    correlationKey: "purchase_request:{$purchaseRequest->id}:approval_required:{$head->id}",
                    subjectType: PurchaseRequest::class,
                    subjectId: $purchaseRequest->id,
                    actionRoute: route('procurement.show', $purchaseRequest->uuid),
                ));

                if ($purchaseRequest->is_duplicate_override) {
                    $this->createNotification->execute(new CreateInboxNotificationInput(
                        recipientId: $head->id,
                        warehouseId: $purchaseRequest->warehouse_id,
                        type: NotificationType::DuplicatePurchaseWarning,
                        title: 'Peringatan Purchase Request Duplikat',
                        message: "Purchase Request {$purchaseRequest->request_number} diajukan meskipun terdeteksi kemungkinan duplikat dengan request lain.",
                        correlationKey: "purchase_request:{$purchaseRequest->id}:duplicate_warning:{$head->id}",
                        subjectType: PurchaseRequest::class,
                        subjectId: $purchaseRequest->id,
                        actionRoute: route('procurement.show', $purchaseRequest->uuid),
                    ));
                }
            }
        } catch (Throwable $e) {
            Log::error('Failed to create purchase request approval-required notification.', [
                'purchase_request_id' => $event->purchaseRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
