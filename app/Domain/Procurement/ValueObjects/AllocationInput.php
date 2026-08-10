<?php

namespace App\Domain\Procurement\ValueObjects;

use InvalidArgumentException;

readonly class AllocationInput
{
    public function __construct(
        public int $purchaseRequestItemId,
        public int $quantity,
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Allocated quantity must be greater than zero.');
        }
    }
}
