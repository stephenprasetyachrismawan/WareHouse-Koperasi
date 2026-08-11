<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Returns\Events\ReturnReadyForRepickup;
use App\Enums\NotificationType;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyReplacementReady
{
    public function __construct(
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(ReturnReadyForRepickup $event): void
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
                type: NotificationType::ReplacementReady,
                title: 'Penggantian Siap Diambil',
                message: "Barang penggantian untuk retur {$returnRequest->return_number} sudah siap diambil di gudang.",
                correlationKey: "return_request:{$returnRequest->id}:replacement_ready",
                subjectType: ReturnRequest::class,
                subjectId: $returnRequest->id,
                actionRoute: route('returns.show', $returnRequest->uuid),
            ));
        } catch (Throwable $e) {
            Log::error('Failed to create replacement-ready notification.', [
                'return_request_id' => $event->returnRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
