<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\PurchaseOrderSent;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SendPurchaseOrderAction
{
    public function execute(User $actor, PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        Gate::forUser($actor)->authorize('send', $purchaseOrder);

        return DB::transaction(function () use ($actor, $purchaseOrder) {
            $purchaseOrder = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);

            if ($purchaseOrder->status !== PurchaseOrderStatus::Draft) {
                throw new Exception('Only DRAFT purchase orders can be sent to a supplier.');
            }

            if ($purchaseOrder->items()->doesntExist()) {
                throw new Exception('Cannot send a Purchase Order without any items.');
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::SentToSupplier->value,
                'sent_by' => $actor->id,
                'sent_at' => now(),
            ]);

            $purchaseRequestIds = PurchaseRequestAllocation::whereHas(
                'purchaseOrderItem',
                fn ($query) => $query->where('purchase_order_id', $purchaseOrder->id)
            )
                ->with('purchaseRequestItem')
                ->get()
                ->pluck('purchaseRequestItem.purchase_request_id')
                ->unique();

            PurchaseRequest::whereIn('id', $purchaseRequestIds)
                ->update(['status' => PurchaseRequestStatus::PoSent->value]);

            event(new PurchaseOrderSent($purchaseOrder, $actor));

            return $purchaseOrder->fresh(['items']);
        });
    }
}
