<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Pickup\Events\PickupRequestReadyForPickup;
use App\Enums\NotificationType;
use App\Models\PickupRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyPickupReadyForPickup
{
    public function __construct(
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(PickupRequestReadyForPickup $event): void
    {
        try {
            $pickupRequest = $event->pickupRequest;

            $this->createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $pickupRequest->user_id,
                warehouseId: $pickupRequest->warehouse_id,
                type: NotificationType::ReadyForPickup,
                title: 'Barang Siap Diambil',
                message: "Pengambilan {$pickupRequest->request_number} sudah siap. Silakan ambil di gudang.",
                correlationKey: "pickup_request:{$pickupRequest->id}:ready",
                subjectType: PickupRequest::class,
                subjectId: $pickupRequest->id,
                actionRoute: route('pickup.show', $pickupRequest->uuid),
            ));
        } catch (Throwable $e) {
            Log::error('Failed to create ready-for-pickup notification.', [
                'pickup_request_id' => $event->pickupRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
