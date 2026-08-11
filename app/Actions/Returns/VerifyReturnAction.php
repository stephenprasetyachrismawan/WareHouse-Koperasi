<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Events\ReturnVerified;
use App\Domain\Returns\ValueObjects\VerifyReturnInput;
use App\Enums\ReturnEvidencePurpose;
use App\Enums\ReturnStatus;
use App\Models\ItemBarcode;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class VerifyReturnAction
{
    public function execute(User $actor, ReturnRequest $returnRequest, VerifyReturnInput $input): ReturnRequest
    {
        Gate::forUser($actor)->authorize('verify', $returnRequest);

        return DB::transaction(function () use ($actor, $returnRequest, $input) {
            $locked = ReturnRequest::with('items')
                ->where('id', $returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->warehouse_id !== $input->warehouseId) {
                throw new RuntimeException('Return request does not belong to the active warehouse.');
            }

            if ($locked->version !== $input->expectedVersion) {
                throw new RuntimeException('This return has already been updated by another action. Please reload and try again.');
            }

            if ($locked->status !== ReturnStatus::Submitted) {
                throw new RuntimeException('Only submitted returns can be verified.');
            }

            $returnRequestItem = $locked->items->first();

            $matchedBarcode = ItemBarcode::where('warehouse_id', $input->warehouseId)
                ->where('barcode', $input->scannedBarcode)
                ->first();

            if (! $matchedBarcode) {
                throw new RuntimeException('Barcode not recognized in this warehouse.');
            }

            if ($matchedBarcode->item_id !== $returnRequestItem->item_id) {
                throw new RuntimeException('Scanned item does not match the item being returned.');
            }

            if ($input->verifiedQuantity !== $returnRequestItem->return_quantity) {
                throw new RuntimeException('Verified quantity does not match the declared return quantity.');
            }

            $returnRequestItem->update([
                'barcode_verified' => true,
                'verified_barcode' => $input->scannedBarcode,
            ]);

            $locked->evidence()->create([
                'warehouse_id' => $input->warehouseId,
                'purpose' => ReturnEvidencePurpose::ReturnVerification,
                'uploaded_by' => $actor->id,
                'path' => $input->evidencePath,
                'mime' => $input->evidenceMime,
            ]);

            $locked->update([
                'status' => ReturnStatus::AdminVerified,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'verification_notes' => $input->notes,
                'version' => $locked->version + 1,
            ]);

            DB::afterCommit(function () use ($locked) {
                ReturnVerified::dispatch($locked->fresh(['items', 'evidence']));
            });

            return $locked->fresh(['items', 'evidence']);
        });
    }
}
