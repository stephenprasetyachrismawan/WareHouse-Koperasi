<?php

namespace App\Actions\Returns;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequestItem;
use Illuminate\Support\Facades\DB;

/**
 * FR-57 fallback: mirrors CreateBackorderPurchaseRequestAction's established
 * pattern (system-generated, no human approval needed to create it, starts
 * at DRAFT and follows the normal Procurement approval flow from there).
 * Idempotent per Return item — but unlike the backorder precedent, only
 * treats a still-in-progress request as blocking; once a prior attempt
 * reaches a terminal state (rejected/cancelled), a fresh one may be created.
 */
class CreateReturnReplacementPurchaseRequestAction
{
    public function execute(ReturnRequestItem $returnRequestItem, int $shortfallQuantity): PurchaseRequest
    {
        return DB::transaction(function () use ($returnRequestItem, $shortfallQuantity) {
            $returnRequest = $returnRequestItem->returnRequest;

            $existing = PurchaseRequest::where('return_request_id', $returnRequest->id)
                ->whereHas('items', function ($query) use ($returnRequestItem) {
                    $query->where('item_id', $returnRequestItem->item_id);
                })
                ->whereNotIn('status', [
                    PurchaseRequestStatus::Rejected,
                    PurchaseRequestStatus::Cancelled,
                ])
                ->first();

            if ($existing) {
                return $existing;
            }

            $purchaseRequest = PurchaseRequest::create([
                'warehouse_id' => $returnRequest->warehouse_id,
                'request_number' => $this->generateRequestNumber(),
                'source' => PurchaseRequestSource::ReturnReplacement,
                'status' => PurchaseRequestStatus::Draft,
                'created_by' => $returnRequest->submitted_by,
                'notes' => "Penggantian retur {$returnRequest->return_number}",
                'return_request_id' => $returnRequest->id,
            ]);

            $purchaseRequest->items()->create([
                'item_id' => $returnRequestItem->item_id,
                'requested_quantity' => $shortfallQuantity,
            ]);

            return $purchaseRequest;
        });
    }

    private function generateRequestNumber(): string
    {
        return sprintf(
            'PR-%s-%s',
            now()->format('Ymd'),
            strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))
        );
    }
}
