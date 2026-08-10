<?php

namespace App\Domain\Procurement\Actions;

use App\Domain\Procurement\Exceptions\DuplicatePurchaseRequestException;
use App\Domain\Procurement\Queries\DetectDuplicatePurchaseRequestQuery;
use App\Domain\Procurement\ValueObjects\PurchaseRequestInput;
use App\Enums\PurchaseRequestStatus;
use App\Events\Procurement\PurchaseRequestCreated;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePurchaseRequestAction
{
    public function __construct(
        private readonly DetectDuplicatePurchaseRequestQuery $duplicateQuery
    ) {}

    public function execute(User $actor, PurchaseRequestInput $input): PurchaseRequest
    {
        return DB::transaction(function () use ($actor, $input) {
            foreach ($input->items as $itemInput) {
                $duplicateCheck = $this->duplicateQuery->execute($input->warehouseId, $itemInput->itemId);

                if ($duplicateCheck['is_duplicate']) {
                    if (! $input->overrideDuplicate) {
                        throw new DuplicatePurchaseRequestException(
                            $duplicateCheck['in_progress_qty'],
                            $duplicateCheck['candidates']
                        );
                    }
                    if (empty($input->overrideReason)) {
                        throw new \InvalidArgumentException('Override reason is required when overriding a duplicate purchase request.');
                    }
                }
            }

            $date = now()->format('Ymd');
            $random = strtoupper(Str::random(8));
            $requestNumber = "PR-{$date}-{$random}";

            $purchaseRequest = PurchaseRequest::create([
                'warehouse_id' => $input->warehouseId,
                'request_number' => $requestNumber,
                'source' => $input->source->value,
                'urgency' => $input->urgency->value,
                'status' => PurchaseRequestStatus::Draft->value,
                'created_by' => $input->userId,
                'notes' => $input->notes,
                'pickup_request_id' => $input->pickupRequestId,
                'is_duplicate_override' => $input->overrideDuplicate,
                'duplicate_override_reason' => $input->overrideDuplicate ? $input->overrideReason : null,
                'duplicate_overridden_by' => $input->overrideDuplicate ? $actor->id : null,
                'duplicate_overridden_at' => $input->overrideDuplicate ? now() : null,
            ]);

            foreach ($input->items as $itemInput) {
                $purchaseRequest->items()->create([
                    'item_id' => $itemInput->itemId,
                    'requested_quantity' => $itemInput->quantity,
                    'notes' => $itemInput->notes,
                ]);
            }

            event(new PurchaseRequestCreated($purchaseRequest));

            return $purchaseRequest->load('items');
        });
    }
}
