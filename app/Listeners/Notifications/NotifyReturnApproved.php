<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Returns\Events\ReturnApproved;
use App\Enums\NotificationType;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Deliberately omits fault_attribution from the message: that field is
 * internal/operational data, gated to returns.verify/returns.approve
 * holders (see ReturnRequestPolicy) — Koperasi never sees it here.
 */
class NotifyReturnApproved
{
    public function __construct(
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(ReturnApproved $event): void
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
                title: 'Retur Disetujui',
                message: "Retur {$returnRequest->return_number} telah disetujui. Penggantian sedang disiapkan.",
                correlationKey: "return_request:{$returnRequest->id}:status",
                subjectType: ReturnRequest::class,
                subjectId: $returnRequest->id,
                actionRoute: route('returns.show', $returnRequest->uuid),
            ));
        } catch (Throwable $e) {
            Log::error('Failed to create return-approved notification.', [
                'return_request_id' => $event->returnRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
