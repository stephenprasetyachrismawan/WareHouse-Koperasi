<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Support\RecipientResolver;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Domain\Procurement\Events\PurchaseOrderSent;
use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyPurchaseOrderSent
{
    public function __construct(
        private readonly RecipientResolver $recipients,
        private readonly CreateInboxNotificationAction $createNotification,
    ) {}

    public function handle(PurchaseOrderSent $event): void
    {
        try {
            $purchaseOrder = $event->purchaseOrder;

            $this->notifyPurchaseRequestCreators($purchaseOrder);
            $this->notifyReceivingStaff($purchaseOrder);
        } catch (Throwable $e) {
            Log::error('Failed to create purchase order sent notifications.', [
                'purchase_order_id' => $event->purchaseOrder->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyPurchaseRequestCreators(PurchaseOrder $purchaseOrder): void
    {
        $creators = $purchaseOrder->allocations()
            ->with('purchaseRequestItem.purchaseRequest')
            ->get()
            ->pluck('purchaseRequestItem.purchaseRequest')
            ->filter()
            ->unique('id');

        foreach ($creators as $purchaseRequest) {
            $this->createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $purchaseRequest->created_by,
                warehouseId: $purchaseOrder->warehouse_id,
                type: NotificationType::PoStatus,
                title: 'Purchase Order Dikirim ke Supplier',
                message: "Purchase Order {$purchaseOrder->po_number} untuk Purchase Request {$purchaseRequest->request_number} telah dikirim ke supplier.",
                correlationKey: "purchase_order:{$purchaseOrder->id}:status:{$purchaseRequest->created_by}",
                subjectType: PurchaseOrder::class,
                subjectId: $purchaseOrder->id,
                actionRoute: route('procurement.purchase-orders.show', $purchaseOrder->uuid),
            ));
        }
    }

    private function notifyReceivingStaff(PurchaseOrder $purchaseOrder): void
    {
        $receivers = $this->recipients->warehouseUsersWithPermission(
            $purchaseOrder->warehouse_id,
            Permission::ReceiptCreate,
        );

        foreach ($receivers as $receiver) {
            $this->createNotification->execute(new CreateInboxNotificationInput(
                recipientId: $receiver->id,
                warehouseId: $purchaseOrder->warehouse_id,
                type: NotificationType::ReceiptRequired,
                title: 'Penerimaan Barang Diperlukan',
                message: "Purchase Order {$purchaseOrder->po_number} telah dikirim. Catat penerimaan barang saat tiba.",
                correlationKey: "purchase_order:{$purchaseOrder->id}:receipt_required:{$receiver->id}",
                subjectType: PurchaseOrder::class,
                subjectId: $purchaseOrder->id,
                actionRoute: route('procurement.purchase-orders.show', $purchaseOrder->uuid),
            ));
        }
    }
}
