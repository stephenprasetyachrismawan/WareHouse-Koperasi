<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Returns\Events\ReturnSubmitted;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyReturnSubmitted
{
    public function __construct(
        private readonly RecipientResolver $recipients,
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(ReturnSubmitted $event): void
    {
        try {
            $returnRequest = $event->returnRequest;

            $staff = $this->recipients->warehouseUsersWithPermission(
                $returnRequest->warehouse_id,
                Permission::ReturnVerify,
            );

            foreach ($staff as $staffUser) {
                $this->createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $staffUser->id,
                    warehouseId: $returnRequest->warehouse_id,
                    type: NotificationType::ReturnSubmitted,
                    title: 'Retur Baru Diajukan',
                    message: "Retur {$returnRequest->return_number} perlu diverifikasi.",
                    correlationKey: "return_request:{$returnRequest->id}:submitted:{$staffUser->id}",
                    subjectType: ReturnRequest::class,
                    subjectId: $returnRequest->id,
                    actionRoute: route('returns.show', $returnRequest->uuid),
                ));
            }
        } catch (Throwable $e) {
            Log::error('Failed to create return-submitted notification.', [
                'return_request_id' => $event->returnRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
