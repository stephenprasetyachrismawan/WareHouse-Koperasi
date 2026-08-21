<?php

namespace Database\Seeders;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Enums\CancellationRequestStatus;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Models\CancellationRequest;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Notification coverage for the Cancellation Request workflow — not
 * demonstrated anywhere else, since DemoJatengProcurementSeeder is the
 * first seeder in this repo to exercise that workflow at all. The generic
 * PR/pickup/return/PO notifications for WH-JATENG are covered by adding it
 * to DemoNotificationSeeder's existing per-warehouse loop, not duplicated
 * here.
 */
class DemoJatengNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-JATENG')->first();
        if (! $warehouse) {
            return;
        }

        $createNotification = app(CreateInboxNotificationAction::class);
        $recipients = app(RecipientResolver::class);
        $heads = $recipients->warehouseUsersWithPermission($warehouse->id, Permission::PurchaseRequestApprove);

        $pendingCancellation = CancellationRequest::where('warehouse_id', $warehouse->id)
            ->where('status', CancellationRequestStatus::Pending->value)
            ->with('purchaseRequest')
            ->first();

        if ($pendingCancellation && $pendingCancellation->purchaseRequest) {
            foreach ($heads as $head) {
                $createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $head->id,
                    warehouseId: $warehouse->id,
                    type: NotificationType::CancellationRequired,
                    title: 'Permintaan Pembatalan Menunggu Keputusan',
                    message: "Purchase Request {$pendingCancellation->purchaseRequest->request_number} memiliki permintaan pembatalan yang perlu Anda putuskan.",
                    correlationKey: "cancellation_request:{$pendingCancellation->id}:required:{$head->id}",
                    subjectType: CancellationRequest::class,
                    subjectId: $pendingCancellation->id,
                    actionRoute: route('procurement.show', $pendingCancellation->purchaseRequest->uuid),
                ));
            }
        }

        $decidedCancellation = CancellationRequest::where('warehouse_id', $warehouse->id)
            ->whereIn('status', [CancellationRequestStatus::Approved->value, CancellationRequestStatus::Rejected->value])
            ->with('purchaseRequest')
            ->first();

        if ($decidedCancellation && $decidedCancellation->purchaseRequest) {
            $decisionLabel = $decidedCancellation->status === CancellationRequestStatus::Approved ? 'disetujui' : 'ditolak';

            $createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $decidedCancellation->requested_by,
                warehouseId: $warehouse->id,
                type: NotificationType::CancellationStatus,
                title: 'Keputusan Pembatalan Purchase Request',
                message: "Permintaan pembatalan Purchase Request {$decidedCancellation->purchaseRequest->request_number} telah {$decisionLabel}.",
                correlationKey: "cancellation_request:{$decidedCancellation->id}:status",
                subjectType: CancellationRequest::class,
                subjectId: $decidedCancellation->id,
                actionRoute: route('procurement.show', $decidedCancellation->purchaseRequest->uuid),
            ));
        }
    }
}
