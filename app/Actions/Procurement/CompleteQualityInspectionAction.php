<?php

namespace App\Actions\Procurement;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Domain\Procurement\Events\GoodsAcceptedIntoStock;
use App\Domain\Procurement\Events\PurchaseOrderCompleted;
use App\Domain\Procurement\Events\QualityInspectionCompleted;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\QualityInspectionResult;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAllocation;
use App\Models\QualityInspection;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CompleteQualityInspectionAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordStockMovement,
    ) {}

    public function execute(User $actor, GoodsReceiptItem $goodsReceiptItem, CompleteQualityInspectionInput $input): QualityInspection
    {
        Gate::forUser($actor)->authorize('create', [QualityInspection::class, $goodsReceiptItem]);

        return DB::transaction(function () use ($actor, $goodsReceiptItem, $input) {
            $goodsReceiptItem = GoodsReceiptItem::with('goodsReceipt')
                ->lockForUpdate()
                ->findOrFail($goodsReceiptItem->id);

            if ($goodsReceiptItem->warehouse_id !== $input->warehouseId) {
                throw new Exception('Goods receipt item does not belong to the active warehouse.');
            }

            if (QualityInspection::where('goods_receipt_item_id', $goodsReceiptItem->id)->exists()) {
                throw new Exception('This receipt item has already received a final quality inspection.');
            }

            $inspection = QualityInspection::create([
                'warehouse_id' => $goodsReceiptItem->warehouse_id,
                'goods_receipt_item_id' => $goodsReceiptItem->id,
                'result' => $input->result->value,
                'condition' => $input->condition->value,
                'notes' => $input->notes,
                'evidence_path' => $input->evidencePath,
                'evidence_mime' => $input->evidenceMime,
                'inspected_by' => $actor->id,
                'inspected_at' => now(),
            ]);

            if ($input->result === QualityInspectionResult::Pass) {
                $stockTransaction = $this->recordStockMovement->execute(new StockMovementInput(
                    warehouseId: $goodsReceiptItem->warehouse_id,
                    itemId: $goodsReceiptItem->item_id,
                    movementType: MovementType::Receipt,
                    quantity: $goodsReceiptItem->received_quantity,
                    performedBy: $actor->id,
                    idempotencyKey: "warehouse:{$goodsReceiptItem->warehouse_id}:goods-receipt-item:{$goodsReceiptItem->id}:stock-in",
                    sourceType: QualityInspection::class,
                    sourceId: $inspection->id,
                ));

                $inspection->update(['stock_transaction_id' => $stockTransaction->id]);

                DB::afterCommit(function () use ($inspection, $stockTransaction) {
                    event(new GoodsAcceptedIntoStock($inspection->fresh(), $stockTransaction));
                });
            }

            $this->progressLifecycle($goodsReceiptItem->goodsReceipt);

            DB::afterCommit(function () use ($inspection) {
                event(new QualityInspectionCompleted($inspection));
            });

            return $inspection->fresh();
        });
    }

    /**
     * Aggregate the receipt's item-level QC results into the GoodsReceipt,
     * PurchaseOrder, and source PurchaseRequest lifecycle states. A Purchase
     * Order only reaches COMPLETED once every one of its receipt items has a
     * PASS inspection with stock accepted; a single FAIL keeps it parked at
     * GOODS_RECEIVED indefinitely (Phase 5 owns resolving that).
     */
    private function progressLifecycle(GoodsReceipt $goodsReceipt): void
    {
        // Locking the PO serializes concurrent QC completions on *different*
        // items of the same receipt, so the aggregate "are all items done"
        // check below can't miss a sibling item that committed a moment ago.
        $purchaseOrder = PurchaseOrder::lockForUpdate()->findOrFail($goodsReceipt->purchase_order_id);

        $items = $goodsReceipt->items()
            ->with(['inspection', 'purchaseOrderItem.allocations.purchaseRequestItem'])
            ->get();

        $allFinalised = $items->every(fn (GoodsReceiptItem $item) => $item->inspection !== null);
        $allPassed = $allFinalised && $items->every(fn (GoodsReceiptItem $item) => $item->inspection->isPass());

        if ($allFinalised && $goodsReceipt->status !== GoodsReceiptStatus::QcCompleted) {
            $goodsReceipt->update(['status' => GoodsReceiptStatus::QcCompleted->value]);
        }

        if ($allPassed && $purchaseOrder->status !== PurchaseOrderStatus::Completed) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Completed->value]);

            DB::afterCommit(function () use ($purchaseOrder) {
                event(new PurchaseOrderCompleted($purchaseOrder->fresh()));
            });
        }

        $purchaseRequestIds = $items
            ->flatMap(fn (GoodsReceiptItem $item) => $item->purchaseOrderItem->allocations->pluck('purchaseRequestItem.purchase_request_id'))
            ->unique();

        foreach ($purchaseRequestIds as $purchaseRequestId) {
            $purchaseRequest = PurchaseRequest::find($purchaseRequestId);

            if (! $purchaseRequest || $purchaseRequest->status !== PurchaseRequestStatus::GoodsReceived) {
                continue;
            }

            if ($this->isPurchaseRequestFullyAccepted($purchaseRequest)) {
                $purchaseRequest->update(['status' => PurchaseRequestStatus::Completed->value]);
            }
        }
    }

    private function isPurchaseRequestFullyAccepted(PurchaseRequest $purchaseRequest): bool
    {
        $itemIds = $purchaseRequest->items()->pluck('id');

        $allocations = PurchaseRequestAllocation::whereIn('purchase_request_item_id', $itemIds)
            ->with('purchaseOrderItem.goodsReceiptItem.inspection')
            ->get();

        if ($allocations->isEmpty()) {
            return false;
        }

        return $allocations->every(function (PurchaseRequestAllocation $allocation) {
            $inspection = $allocation->purchaseOrderItem?->goodsReceiptItem?->inspection;

            return $inspection !== null && $inspection->isPass();
        });
    }
}
