<?php

namespace Database\Seeders;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\ReturnStatus;
use App\Models\PickupRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * When the Return/PO demo seeders and this seeder run together in the same
 * pass, most notification data already appears organically — those seeders
 * run through real Actions, so their domain events reach the listeners
 * registered in NotificationServiceProvider directly. But on a database
 * where those records were created in an earlier phase (before the
 * notification listeners existed), no event ever fired for them. This
 * seeder is therefore self-sufficient: it backfills notifications directly
 * for existing PurchaseRequest/PickupRequest/PurchaseOrder/ReturnRequest
 * rows regardless of when they were created, using the exact same
 * correlation-key idempotency guarantee as the real listeners.
 */
class DemoNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedForWarehouse('WH-PUSAT');
        $this->seedForWarehouse('WH-BARAT');
    }

    private function seedForWarehouse(string $code): void
    {
        $warehouse = Warehouse::where('code', $code)->first();
        if (! $warehouse) {
            return;
        }

        $createNotification = app(CreateInboxNotificationAction::class);
        $recipients = app(RecipientResolver::class);

        $heads = $recipients->warehouseUsersWithPermission($warehouse->id, Permission::PurchaseRequestApprove);

        $waitingPr = PurchaseRequest::where('warehouse_id', $warehouse->id)
            ->where('status', PurchaseRequestStatus::WaitingApproval->value)
            ->first();
        if ($waitingPr) {
            foreach ($heads as $head) {
                $createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $head->id,
                    warehouseId: $warehouse->id,
                    type: NotificationType::ApprovalRequired,
                    title: 'Persetujuan Purchase Request Diperlukan',
                    message: "Purchase Request {$waitingPr->request_number} menunggu persetujuan Anda.",
                    correlationKey: "purchase_request:{$waitingPr->id}:approval_required:{$head->id}",
                    subjectType: PurchaseRequest::class,
                    subjectId: $waitingPr->id,
                    actionRoute: route('procurement.show', $waitingPr->uuid),
                ));
            }
        }

        $approvedPr = PurchaseRequest::where('warehouse_id', $warehouse->id)
            ->where('status', PurchaseRequestStatus::Approved->value)
            ->first();
        if ($approvedPr) {
            $createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $approvedPr->created_by,
                warehouseId: $warehouse->id,
                type: NotificationType::ApprovalApproved,
                title: 'Purchase Request Disetujui',
                message: "Purchase Request {$approvedPr->request_number} telah disetujui.",
                correlationKey: "purchase_request:{$approvedPr->id}:approved",
                subjectType: PurchaseRequest::class,
                subjectId: $approvedPr->id,
                actionRoute: route('procurement.show', $approvedPr->uuid),
            ));
        }

        $rejectedPr = PurchaseRequest::where('warehouse_id', $warehouse->id)
            ->where('status', PurchaseRequestStatus::Rejected->value)
            ->first();
        if ($rejectedPr) {
            $createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $rejectedPr->created_by,
                warehouseId: $warehouse->id,
                type: NotificationType::ApprovalRejected,
                title: 'Purchase Request Ditolak',
                message: "Purchase Request {$rejectedPr->request_number} ditolak. Alasan: {$rejectedPr->notes}",
                correlationKey: "purchase_request:{$rejectedPr->id}:rejected",
                subjectType: PurchaseRequest::class,
                subjectId: $rejectedPr->id,
                actionRoute: route('procurement.show', $rejectedPr->uuid),
            ));
        }

        $staff = $recipients->warehouseUsersWithPermission($warehouse->id, Permission::PickupRequestPrepare);

        $submittedPickup = PickupRequest::where('warehouse_id', $warehouse->id)
            ->where('status', 'SUBMITTED')
            ->first();
        if ($submittedPickup) {
            foreach ($staff as $staffUser) {
                $createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $staffUser->id,
                    warehouseId: $warehouse->id,
                    type: NotificationType::PickupRequested,
                    title: 'Permintaan Pengambilan Baru',
                    message: "Request pengambilan {$submittedPickup->request_number} perlu diperiksa.",
                    correlationKey: "pickup_request:{$submittedPickup->id}:requested:{$staffUser->id}",
                    subjectType: PickupRequest::class,
                    subjectId: $submittedPickup->id,
                    actionRoute: route('pickup.show', $submittedPickup->uuid),
                ));
            }
        }

        $readyPickup = PickupRequest::where('warehouse_id', $warehouse->id)
            ->where('status', 'READY_FOR_PICKUP')
            ->first();
        if ($readyPickup) {
            $createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $readyPickup->user_id,
                warehouseId: $warehouse->id,
                type: NotificationType::ReadyForPickup,
                title: 'Barang Siap Diambil',
                message: "Pengambilan {$readyPickup->request_number} sudah siap. Silakan ambil di gudang.",
                correlationKey: "pickup_request:{$readyPickup->id}:ready",
                subjectType: PickupRequest::class,
                subjectId: $readyPickup->id,
                actionRoute: route('pickup.show', $readyPickup->uuid),
            ));
        }

        $this->seedReturnNotifications($warehouse, $createNotification, $recipients);
        $this->seedPurchaseOrderNotifications($warehouse, $createNotification);
    }

    private function seedReturnNotifications(Warehouse $warehouse, CreateInboxNotificationAction $createNotification, RecipientResolver $recipients): void
    {
        $staff = $recipients->warehouseUsersWithPermission($warehouse->id, Permission::ReturnVerify);

        $submittedReturn = ReturnRequest::where('warehouse_id', $warehouse->id)
            ->where('status', ReturnStatus::Submitted->value)
            ->first();
        if ($submittedReturn) {
            foreach ($staff as $staffUser) {
                $createNotification->execute(new CreateInboxNotificationInput(
                    recipientId: $staffUser->id,
                    warehouseId: $warehouse->id,
                    type: NotificationType::ReturnSubmitted,
                    title: 'Retur Baru Diajukan',
                    message: "Retur {$submittedReturn->return_number} perlu diverifikasi.",
                    correlationKey: "return_request:{$submittedReturn->id}:submitted:{$staffUser->id}",
                    subjectType: ReturnRequest::class,
                    subjectId: $submittedReturn->id,
                    actionRoute: route('returns.show', $submittedReturn->uuid),
                ));
            }
        }

        $rejectedReturn = ReturnRequest::where('warehouse_id', $warehouse->id)
            ->where('status', ReturnStatus::Rejected->value)
            ->first();
        if ($rejectedReturn && $rejectedReturn->cooperativeMembership) {
            $createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $rejectedReturn->cooperativeMembership->user_id,
                warehouseId: $warehouse->id,
                type: NotificationType::ReturnStatus,
                title: 'Retur Ditolak',
                message: "Retur {$rejectedReturn->return_number} ditolak. Alasan: {$rejectedReturn->decision_notes}",
                correlationKey: "return_request:{$rejectedReturn->id}:status",
                subjectType: ReturnRequest::class,
                subjectId: $rejectedReturn->id,
                actionRoute: route('returns.show', $rejectedReturn->uuid),
            ));
        }

        $replacementReadyReturn = ReturnRequest::where('warehouse_id', $warehouse->id)
            ->whereIn('status', [ReturnStatus::ReadyForRepickup->value, ReturnStatus::Completed->value])
            ->first();
        if ($replacementReadyReturn && $replacementReadyReturn->cooperativeMembership) {
            $createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $replacementReadyReturn->cooperativeMembership->user_id,
                warehouseId: $warehouse->id,
                type: NotificationType::ReplacementReady,
                title: 'Penggantian Siap Diambil',
                message: "Barang penggantian untuk retur {$replacementReadyReturn->return_number} sudah siap diambil di gudang.",
                correlationKey: "return_request:{$replacementReadyReturn->id}:replacement_ready",
                subjectType: ReturnRequest::class,
                subjectId: $replacementReadyReturn->id,
                actionRoute: route('returns.show', $replacementReadyReturn->uuid),
            ));
        }
    }

    private function seedPurchaseOrderNotifications(Warehouse $warehouse, CreateInboxNotificationAction $createNotification): void
    {
        $sentPo = PurchaseOrder::where('warehouse_id', $warehouse->id)
            ->where('status', PurchaseOrderStatus::SentToSupplier->value)
            ->first();
        if (! $sentPo) {
            return;
        }

        $purchaseRequest = $sentPo->allocations()
            ->with('purchaseRequestItem.purchaseRequest')
            ->get()
            ->pluck('purchaseRequestItem.purchaseRequest')
            ->filter()
            ->first();

        if ($purchaseRequest) {
            $createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $purchaseRequest->created_by,
                warehouseId: $warehouse->id,
                type: NotificationType::PoStatus,
                title: 'Purchase Order Dikirim ke Supplier',
                message: "Purchase Order {$sentPo->po_number} telah dikirim ke supplier.",
                correlationKey: "purchase_order:{$sentPo->id}:status:{$purchaseRequest->created_by}",
                subjectType: PurchaseOrder::class,
                subjectId: $sentPo->id,
                actionRoute: route('procurement.purchase-orders.show', $sentPo->uuid),
            ));
        }
    }
}
