<?php

namespace App\Domain\Returns\ValueObjects;

use App\Enums\ReturnReasonCode;
use InvalidArgumentException;

readonly class CreateReturnInput
{
    public function __construct(
        public int $warehouseId,
        public int $pickupRequestId,
        public int $pickupRequestItemId,
        public int $returnQuantity,
        public ReturnReasonCode $reasonCode,
        public ?string $reasonNotes,
        public string $evidencePath,
        public string $evidenceMime,
    ) {
        if ($this->returnQuantity <= 0) {
            throw new InvalidArgumentException('Return quantity must be greater than zero.');
        }

        if ($this->reasonCode->requiresNotes() && trim((string) $this->reasonNotes) === '') {
            throw new InvalidArgumentException('Reason notes are required when reason is OTHER.');
        }
    }
}
