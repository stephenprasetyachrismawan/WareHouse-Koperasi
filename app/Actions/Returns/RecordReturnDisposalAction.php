<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Events\ReturnDisposed;
use App\Models\ReturnDisposal;
use App\Models\ReturnRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Internal collaborator invoked only by ApproveReturnAction, inside its
 * transaction and lock. Purely an audit/traceability record — the returned
 * item was already removed from StockBalance at Pickup time, so disposal
 * must NEVER touch StockBalance (no increment, no decrement, no
 * StockTransaction, no RecordStockMovementAction call).
 */
class RecordReturnDisposalAction
{
    public function execute(User $actor, ReturnRequestItem $returnRequestItem): ReturnDisposal
    {
        $existing = ReturnDisposal::where('return_request_item_id', $returnRequestItem->id)->first();
        if ($existing) {
            return $existing;
        }

        $disposal = ReturnDisposal::create([
            'return_request_id' => $returnRequestItem->return_request_id,
            'return_request_item_id' => $returnRequestItem->id,
            'warehouse_id' => $returnRequestItem->returnRequest->warehouse_id,
            'item_id' => $returnRequestItem->item_id,
            'quantity' => $returnRequestItem->return_quantity,
            'disposed_by' => $actor->id,
            'disposed_at' => now(),
        ]);

        DB::afterCommit(function () use ($disposal) {
            ReturnDisposed::dispatch($disposal);
        });

        return $disposal;
    }
}
