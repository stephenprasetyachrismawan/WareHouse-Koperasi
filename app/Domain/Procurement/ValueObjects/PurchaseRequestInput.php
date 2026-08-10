<?php

namespace App\Domain\Procurement\ValueObjects;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestUrgency;

readonly class PurchaseRequestInput
{
    /**
     * @param  PurchaseRequestItemInput[]  $items
     */
    public function __construct(
        public int $warehouseId,
        public int $userId,
        public PurchaseRequestSource $source,
        public PurchaseRequestUrgency $urgency,
        public ?string $notes,
        public array $items,
        public ?int $pickupRequestId = null,
        public bool $overrideDuplicate = false,
        public ?string $overrideReason = null,
    ) {}
}
