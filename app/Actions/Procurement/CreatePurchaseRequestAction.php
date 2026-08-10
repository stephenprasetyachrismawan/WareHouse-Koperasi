<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Enums\PurchaseRequestUrgency;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePurchaseRequestAction
{
    public function execute(User $user, array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $date = now()->format('Ymd');
            $latest = PurchaseRequest::where('request_number', 'like', "PR-{$date}-%")
                ->orderBy('request_number', 'desc')
                ->lockForUpdate()
                ->first();

            $sequence = 1;
            if ($latest) {
                $lastSequence = (int) substr($latest->request_number, -4);
                $sequence = $lastSequence + 1;
            }
            $requestNumber = sprintf('PR-%s-%04d', $date, $sequence);

            $purchaseRequest = PurchaseRequest::create([
                'warehouse_id' => $data['warehouse_id'],
                'request_number' => $requestNumber,
                'source' => $data['source'] ?? PurchaseRequestSource::Manual,
                'urgency' => $data['urgency'] ?? PurchaseRequestUrgency::Normal,
                'status' => PurchaseRequestStatus::Draft,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
                'is_duplicate_override' => $data['is_duplicate_override'] ?? false,
                'duplicate_override_reason' => $data['duplicate_override_reason'] ?? null,
                'duplicate_overridden_by' => ($data['is_duplicate_override'] ?? false) ? $user->id : null,
                'duplicate_overridden_at' => ($data['is_duplicate_override'] ?? false) ? now() : null,
            ]);

            foreach ($data['items'] as $item) {
                $purchaseRequest->items()->create([
                    'item_id' => $item['item_id'],
                    'requested_quantity' => $item['quantity'],
                ]);
            }

            return $purchaseRequest;
        });
    }
}
