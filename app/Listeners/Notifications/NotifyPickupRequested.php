<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Pickup\Events\PickupRequestSubmitted;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Models\PickupRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyPickupRequested
{
    public function __construct(
        private readonly RecipientResolver $recipients,
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(PickupRequestSubmitted $event): void
    {
        try {
            $pickupRequest = $event->pickupRequest;

            $staff = $this->recipients->warehouseUsersWithPermission(
                $pickupRequest->warehouse_id,
                Permission::PickupRequestPrepare,
            );

            foreach ($staff as $staffUser) {
                $this->createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $staffUser->id,
                    warehouseId: $pickupRequest->warehouse_id,
                    type: NotificationType::PickupRequested,
                    title: 'Permintaan Pengambilan Baru',
                    message: "Request pengambilan {$pickupRequest->request_number} perlu diperiksa.",
                    correlationKey: "pickup_request:{$pickupRequest->id}:requested:{$staffUser->id}",
                    subjectType: PickupRequest::class,
                    subjectId: $pickupRequest->id,
                    actionRoute: route('pickup.show', $pickupRequest->uuid),
                ));
            }
        } catch (Throwable $e) {
            Log::error('Failed to create pickup-requested notification.', [
                'pickup_request_id' => $event->pickupRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
