<?php

namespace App\Actions\Procurement;

use App\Domain\Procurement\Events\GoodsReceiptRecorded;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RecordGoodsReceiptAction
{
    /**
     * Records the (full, non-partial) physical arrival of a Purchase Order's
     * goods. This does NOT mutate stock — it only advances the PO/PR
     * lifecycle and opens the QC workflow. Stock is only ever mutated by
     * CompleteQualityInspectionAction on a PASS result.
     */
    public function execute(User $actor, RecordGoodsReceiptInput $input): GoodsReceipt
    {
        Gate::forUser($actor)->authorize('create', GoodsReceipt::class);

        return DB::transaction(function () use ($actor, $input) {
            $purchaseOrder = PurchaseOrder::with('items.item')
                ->lockForUpdate()
                ->findOrFail($input->purchaseOrderId);

            if ($purchaseOrder->warehouse_id !== $input->warehouseId) {
                throw new Exception('Purchase order does not belong to the active warehouse.');
            }

            if ($purchaseOrder->status !== PurchaseOrderStatus::SentToSupplier) {
                throw new Exception('Only purchase orders with status SENT_TO_SUPPLIER can be received.');
            }

            if ($purchaseOrder->items->isEmpty()) {
                throw new Exception('Purchase order has no items to receive.');
            }

            foreach ($purchaseOrder->items as $poItem) {
                if (! array_key_exists($poItem->id, $input->receivedQuantities)) {
                    throw new Exception("Missing received quantity for purchase order item #{$poItem->id}.");
                }

                $received = (int) $input->receivedQuantities[$poItem->id];

                if ($received !== $poItem->ordered_quantity) {
                    $itemName = $poItem->item->name ?? "item #{$poItem->item_id}";

                    throw new Exception(
                        "Received quantity for '{$itemName}' ({$received}) must equal the ordered quantity ({$poItem->ordered_quantity}). Partial receipt is not supported in this version."
                    );
                }
            }

            $receiptNumber = $this->generateReceiptNumber($purchaseOrder->warehouse_id);

            $receipt = GoodsReceipt::create([
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'purchase_order_id' => $purchaseOrder->id,
                'receipt_number' => $receiptNumber,
                'received_by' => $actor->id,
                'received_at' => now(),
                'status' => GoodsReceiptStatus::PendingQc->value,
                'notes' => $input->notes,
            ]);

            foreach ($purchaseOrder->items as $poItem) {
                $receipt->items()->create([
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'purchase_order_item_id' => $poItem->id,
                    'item_id' => $poItem->item_id,
                    'expected_quantity' => $poItem->ordered_quantity,
                    'received_quantity' => (int) $input->receivedQuantities[$poItem->id],
                ]);
            }

            $purchaseOrder->update(['status' => PurchaseOrderStatus::GoodsReceived->value]);

            $purchaseRequestIds = PurchaseRequestAllocation::whereHas(
                'purchaseOrderItem',
                fn ($query) => $query->where('purchase_order_id', $purchaseOrder->id)
            )
                ->with('purchaseRequestItem')
                ->get()
                ->pluck('purchaseRequestItem.purchase_request_id')
                ->unique();

            PurchaseRequest::whereIn('id', $purchaseRequestIds)
                ->where('status', PurchaseRequestStatus::PoSent->value)
                ->update(['status' => PurchaseRequestStatus::GoodsReceived->value]);

            DB::afterCommit(function () use ($receipt, $actor) {
                event(new GoodsReceiptRecorded($receipt, $actor));
            });

            return $receipt->load('items.item');
        });
    }

    private function generateReceiptNumber(int $warehouseId): string
    {
        $date = now()->format('Ymd');

        Warehouse::query()->whereKey($warehouseId)->lockForUpdate()->firstOrFail();

        $sequence = GoodsReceipt::forWarehouse($warehouseId)
            ->where('receipt_number', 'like', "GR-{$date}-%")
            ->count() + 1;

        return sprintf('GR-%s-%04d', $date, $sequence);
    }
}
