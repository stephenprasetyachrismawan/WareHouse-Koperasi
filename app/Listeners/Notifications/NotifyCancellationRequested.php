<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Procurement\Events\CancellationRequested;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Models\CancellationRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyCancellationRequested
{
    public function __construct(
        private readonly RecipientResolver $recipients,
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(CancellationRequested $event): void
    {
        try {
            $cancellationRequest = $event->cancellationRequest;

            $heads = $this->recipients->warehouseUsersWithPermission(
                $cancellationRequest->warehouse_id,
                Permission::PurchaseRequestCancel,
            );

            foreach ($heads as $head) {
                $this->createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $head->id,
                    warehouseId: $cancellationRequest->warehouse_id,
                    type: NotificationType::CancellationRequired,
                    title: 'Permintaan Pembatalan Menunggu Keputusan',
                    message: 'Ada permintaan pembatalan Purchase Request yang menunggu keputusan Anda.',
                    correlationKey: "cancellation_request:{$cancellationRequest->id}:required:{$head->id}",
                    subjectType: CancellationRequest::class,
                    subjectId: $cancellationRequest->id,
                    actionRoute: route('procurement.show', $cancellationRequest->purchaseRequest->uuid),
                ));
            }
        } catch (Throwable $e) {
            Log::error('Failed to create cancellation-required notification.', [
                'cancellation_request_id' => $event->cancellationRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
